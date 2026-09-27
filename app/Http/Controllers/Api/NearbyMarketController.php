<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Market;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Throwable;

class NearbyMarketController extends Controller
{
    /**
     * GET /api/markets/nearby?latitude=&longitude=&radius=
     *
     * Reverse-geocodes the visitor's coordinates via OpenStreetMap
     * Nominatim (custom User-Agent per OSM usage policy) and returns
     * active markets within the radius, each with its Haversine
     * distance, sorted nearest-first.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius' => ['nullable', 'numeric', 'min:0.1', 'max:500'],
        ]);

        $latitude = (float) $validated['latitude'];
        $longitude = (float) $validated['longitude'];
        $radiusKm = (float) ($validated['radius'] ?? 10);

        $markets = Market::query()
            ->nearby($latitude, $longitude, $radiusKm)
            ->withCount(['farmerMarkets as farmers_count' => fn ($q) => $q->where('status', 'active')])
            ->get()
            ->map(fn (Market $market) => [
                'id' => $market->id,
                'name' => $market->name,
                'address' => $market->address,
                'latitude' => (float) $market->latitude,
                'longitude' => (float) $market->longitude,
                'distance_km' => round((float) $market->distance_km, 2),
                'farmers_count' => (int) $market->farmers_count,
                'status' => $market->status,
                'url' => route('markets.show', $market),
            ])
            ->values();

        return response()->json([
            'user_location' => [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'address' => $this->reverseGeocode($latitude, $longitude),
            ],
            'radius_km' => $radiusKm,
            'markets' => $markets,
        ]);
    }

    /**
     * Human-readable address for coordinates via OSM Nominatim.
     * Fails soft on purpose: a Nominatim outage or sandbox without
     * internet must never hide the market results.
     */
    private function reverseGeocode(float $latitude, float $longitude): ?string
    {
        try {
            $response = Http::withHeaders([
                // OSM usage policy requires an identifying User-Agent.
                'User-Agent' => 'MarketLink/1.0 (farmers market pre-order app; https://marketlink.test; hello@marketlink.test)',
                'Accept' => 'application/json',
            ])
                ->timeout(5)
                ->get('https://nominatim.openstreetmap.org/reverse', [
                    'format' => 'jsonv2',
                    'lat' => $latitude,
                    'lon' => $longitude,
                    'zoom' => 16,
                    'addressdetails' => 1,
                ]);

            if ($response->failed()) {
                return null;
            }

            return $response->json('display_name');
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }
}
