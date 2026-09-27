<?php

namespace App\Http\Controllers;

use App\Models\FavoriteMarket;
use App\Models\Market;
use App\Models\MarketSchedule;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MarketController extends Controller
{
    public function index(Request $request)
    {
        $day = $request->query('day'); // 0-6 or null

        $markets = Market::query()
            ->with(['schedules', 'farmerMarkets.farmer.user'])
            ->withCount(['farmerMarkets as farmers_count' => fn ($q) => $q->where('status', 'active')])
            ->where('status', 'active')
            ->when($day !== null && $day !== '', fn ($q) => $q->whereHas('schedules', fn ($s) => $s
                ->where('day_of_week', (int) $day)
                ->where('is_closed', false)))
            ->orderBy('name')
            ->paginate(9)
            ->withQueryString();

        // Favorite set for the market cards (one query instead of one per card).
        $favoriteMarketIds = collect();
        if ($request->user()?->isCustomer()) {
            $favoriteMarketIds = FavoriteMarket::where('customer_id', $request->user()->customerProfile->id)
                ->pluck('market_id');
        }

        return view('markets.index', [
            'markets' => $markets,
            'days' => MarketSchedule::DAYS,
            'selectedDay' => $day,
            'favoriteMarketIds' => $favoriteMarketIds,
        ]);
    }

    public function show(Market $market)
    {
        $market->load(['schedules', 'farmerMarkets.farmer.user', 'farmerMarkets.schedules']);

        $stalls = $market->farmerMarkets->where('status', 'active')->values();

        $featuredProducts = Product::query()
            ->where('status', 'active')
            ->whereHas('farmer', fn ($f) => $f->where('approval_status', 'approved'))
            ->whereHas('weeklyStocks', fn ($q) => $q
                ->whereIn('farmer_market_id', $stalls->pluck('id'))
                ->where('week_start', '>=', now()->startOfWeek(Carbon::SUNDAY)->toDateString())
                ->where('status', 'available')
                ->where('available_quantity', '>', 0))
            ->with(['farmer.user', 'category'])
            ->inRandomOrder()
            ->take(8)
            ->get();

        return view('markets.show', [
            'market' => $market,
            'stalls' => $stalls,
            'featuredProducts' => $featuredProducts,
        ]);
    }

    /**
     * GeoJSON feed used by the OpenStreetMap/Leaflet map.
     */
    public function mapData()
    {
        $markets = Market::where('status', 'active')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->withCount(['farmerMarkets as farmers_count' => fn ($q) => $q->where('status', 'active')])
            ->get()
            ->map(fn ($m) => [
                'type' => 'Feature',
                'geometry' => ['type' => 'Point', 'coordinates' => [(float) $m->longitude, (float) $m->latitude]],
                'properties' => [
                    'name' => $m->name,
                    'address' => $m->address,
                    'farmers' => $m->farmers_count,
                    'url' => route('markets.show', $m),
                ],
            ]);

        return response()->json(['type' => 'FeatureCollection', 'features' => $markets]);
    }
}
