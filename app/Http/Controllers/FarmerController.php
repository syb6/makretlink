<?php

namespace App\Http\Controllers;

use App\Models\FarmerProfile;
use App\Models\WeeklyStock;
use Illuminate\Http\Request;

class FarmerController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('q');
        $marketId = $request->query('market');

        $farmers = FarmerProfile::query()
            ->with(['user', 'farmerMarkets.market'])
            ->where('approval_status', 'approved')
            ->when($search, fn ($q) => $q->where(fn ($w) => $w
                ->where('business_name', 'like', "%{$search}%")
                ->orWhere('contact_person', 'like', "%{$search}%")
                ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"))))
            ->when($marketId, fn ($q) => $q->whereHas('farmerMarkets', fn ($fm) => $fm
                ->where('market_id', $marketId)
                ->where('status', 'active')
                // Only count stalls at active markets
                ->whereHas('market', fn ($m) => $m->where('status', 'active'))))
            ->orderBy('business_name')
            ->paginate(12)
            ->withQueryString();

        return view('farmers.index', [
            'farmers' => $farmers,
            'markets' => \App\Models\Market::where('status', 'active')->orderBy('name')->get(),
            'search' => $search,
            'selectedMarket' => $marketId,
        ]);
    }

    public function show(FarmerProfile $farmer)
    {
        abort_unless($farmer->isApproved(), 404);

        $farmer->load(['user', 'farmerMarkets.market', 'farmerMarkets.schedules']);

        $stocks = WeeklyStock::query()
            ->whereHas('product', fn ($q) => $q->where('farmer_id', $farmer->id))
            ->where('status', 'available')
            ->where('available_quantity', '>', 0)
            ->with(['product.category', 'farmerMarket.market'])
            ->orderByDesc('week_start')
            ->get();

        return view('farmers.show', [
            'farmer' => $farmer,
            'stocks' => $stocks,
        ]);
    }
}
