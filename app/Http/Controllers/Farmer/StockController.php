<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\FarmerMarket;
use App\Models\StockTemplate;
use App\Models\WeeklyStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

class StockController extends Controller
{
    private function farmer(): \App\Models\FarmerProfile
    {
        return Auth::user()->farmerProfile;
    }

    private function myFarmerMarketIds(): array
    {
        return FarmerMarket::where('farmer_id', $this->farmer()->id)->pluck('id')->all();
    }

    public function index()
    {
        $weekStart = request()->query('week')
            ? Carbon::parse(request()->query('week'))->startOfWeek(Carbon::SUNDAY)
            : now()->startOfWeek(Carbon::SUNDAY);

        $stocks = WeeklyStock::with(['product', 'farmerMarket.market'])
            ->whereIn('farmer_market_id', $this->myFarmerMarketIds())
            ->where('week_start', $weekStart->toDateString())
            ->orderBy('product_id')
            ->get();

        return view('farmer.stock.index', [
            'stocks' => $stocks,
            'weekStart' => $weekStart,
            'prevWeek' => $weekStart->copy()->subWeek()->toDateString(),
            'nextWeek' => $weekStart->copy()->addWeek()->toDateString(),
            'farmerMarkets' => FarmerMarket::with('market')->where('farmer_id', $this->farmer()->id)->get(),
            'products' => $this->farmer()->products()->where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'farmer_market_id' => ['required', 'exists:farmer_markets,id'],
            'week_start' => ['required', 'date'],
            'quantity' => ['required', 'numeric', 'min:0'],
        ]);

        abort_unless(in_array($data['farmer_market_id'], $this->myFarmerMarketIds(), true), 403);
        abort_unless($this->farmer()->products()->whereKey($data['product_id'])->exists(), 403);

        $weekStart = Carbon::parse($data['week_start'])->startOfWeek(Carbon::SUNDAY)->toDateString();

        WeeklyStock::updateOrCreate(
            [
                'product_id' => $data['product_id'],
                'farmer_market_id' => $data['farmer_market_id'],
                'week_start' => $weekStart,
            ],
            [
                'quantity' => $data['quantity'],
                'available_quantity' => $data['quantity'],
                'status' => $data['quantity'] > 0 ? 'available' : 'unavailable',
            ]
        );

        return back()->with('success', 'Weekly stock saved.');
    }

    public function update(Request $request, WeeklyStock $stock)
    {
        abort_unless(in_array($stock->farmer_market_id, $this->myFarmerMarketIds(), true), 403);

        $data = $request->validate([
            'quantity' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:available,sold_out,unavailable'],
        ]);

        $sold = (float) $stock->quantity - (float) $stock->available_quantity;

        // Keep availability consistent: sold units stay sold; status may override availability.
        $stock->quantity = $data['quantity'];
        $stock->available_quantity = match ($data['status']) {
            'available' => max(0, $data['quantity'] - $sold),
            'sold_out', 'unavailable' => 0,
        };

        if ($data['status'] === 'available' && $stock->available_quantity == 0 && $data['quantity'] > $sold) {
            $stock->available_quantity = $data['quantity'] - $sold;
        }

        $stock->status = $data['status'];
        $stock->save();

        return back()->with('success', 'Stock updated.');
    }

    public function saveTemplate(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'farmer_market_id' => ['required', 'exists:farmer_markets,id'],
            'default_quantity' => ['required', 'numeric', 'min:0'],
        ]);

        abort_unless(in_array($data['farmer_market_id'], $this->myFarmerMarketIds(), true), 403);

        StockTemplate::updateOrCreate(
            ['product_id' => $data['product_id'], 'farmer_market_id' => $data['farmer_market_id']],
            ['default_quantity' => $data['default_quantity'], 'is_active' => true]
        );

        return back()->with('success', 'Template saved — reuse it when creating weekly stock.');
    }

    /** Copy templates into the given week as new weekly stock rows. */
    public function applyTemplates(Request $request)
    {
        $data = $request->validate([
            'week_start' => ['required', 'date'],
            'farmer_market_id' => ['required', 'exists:farmer_markets,id'],
        ]);

        abort_unless(in_array($data['farmer_market_id'], $this->myFarmerMarketIds(), true), 403);

        $weekStart = Carbon::parse($data['week_start'])->startOfWeek(Carbon::SUNDAY)->toDateString();
        $templates = StockTemplate::with('product')
            ->where('farmer_market_id', $data['farmer_market_id'])
            ->where('is_active', true)
            ->get();

        $created = 0;
        foreach ($templates as $tpl) {
            $exists = WeeklyStock::where('product_id', $tpl->product_id)
                ->where('farmer_market_id', $data['farmer_market_id'])
                ->where('week_start', $weekStart)
                ->exists();

            if (! $exists && $tpl->default_quantity > 0) {
                WeeklyStock::create([
                    'product_id' => $tpl->product_id,
                    'farmer_market_id' => $data['farmer_market_id'],
                    'stock_template_id' => $tpl->id,
                    'week_start' => $weekStart,
                    'quantity' => $tpl->default_quantity,
                    'available_quantity' => $tpl->default_quantity,
                    'status' => 'available',
                ]);
                $created++;
            }
        }

        return back()->with('success', $created.' stock row(s) created from templates.');
    }
}
