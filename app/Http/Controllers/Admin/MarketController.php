<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Market;
use App\Models\MarketSchedule;
use Illuminate\Http\Request;

class MarketController extends Controller
{
    public function index()
    {
        return view('admin.markets.index', [
            'markets' => Market::with('schedules')->withCount('farmerMarkets')->orderBy('name')->paginate(10),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = null; // not used by schema; kept for clarity

        $market = Market::create(collect($data)->except('slug')->all());

        $this->saveSchedules($request, $market);

        return back()->with('success', 'Market created.');
    }

    public function update(Request $request, Market $market)
    {
        $market->update($this->validated($request));
        $this->saveSchedules($request, $market);

        return back()->with('success', 'Market updated.');
    }

    public function destroy(Market $market)
    {
        // Markets with stalls/orders must not be deleted: stalls would be orphaned
        // and farmer dashboards (which query stalls directly) would break.
        $hasStalls = $market->farmerMarkets()->exists();

        if ($hasStalls) {
            return back()->with('error', 'Cannot delete: this market has farmer stalls or orders attached. Set it inactive instead.');
        }

        try {
            $market->delete();
        } catch (\Illuminate\Database\QueryException $e) {
            return back()->with('error', 'Cannot delete: this market has orders attached. Set it inactive instead.');
        }

        return back()->with('success', 'Market removed.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'address' => ['required', 'string', 'max:1000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', 'in:active,inactive'],
        ]);
    }

    private function saveSchedules(Request $request, Market $market): void
    {
        $days = $request->input('days', []);

        foreach (range(0, 6) as $d) {
            $open = $days[$d]['open'] ?? null;
            $close = $days[$d]['close'] ?? null;

            if ($open && $close) {
                MarketSchedule::updateOrCreate(
                    ['market_id' => $market->id, 'day_of_week' => $d],
                    ['opening_time' => $open, 'closing_time' => $close, 'is_closed' => false]
                );
            } else {
                MarketSchedule::updateOrCreate(
                    ['market_id' => $market->id, 'day_of_week' => $d],
                    ['opening_time' => null, 'closing_time' => null, 'is_closed' => true]
                );
            }
        }
    }
}
