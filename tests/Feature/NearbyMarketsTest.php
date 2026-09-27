<?php

namespace Tests\Feature;

use App\Models\FarmerMarket;
use App\Models\FarmerProfile;
use App\Models\Market;
use App\Models\MarketSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NearbyMarketsTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    private Market $nearbyMarket;

    private Market $farMarket;

    protected function setUp(): void
    {
        parent::setUp();

        // Nominatim is external — never hit the real service in tests.
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([
                'display_name' => 'Test Address 1, Karachi, Pakistan',
            ]),
        ]);

        $this->customer = User::factory()->create(['role' => 'customer']);

        // ~0.9 km from the query point (24.9000, 67.0500).
        $this->nearbyMarket = Market::create([
            'name' => 'Near Market',
            'address' => '1 Close Street, Karachi',
            'latitude' => 24.9050000,
            'longitude' => 67.0550000,
            'status' => 'active',
        ]);

        // ~24 km from the query point.
        $this->farMarket = Market::create([
            'name' => 'Far Market',
            'address' => '9 Distant Road, Karachi',
            'latitude' => 24.7100000,
            'longitude' => 67.1600000,
            'status' => 'active',
        ]);

        foreach ([$this->nearbyMarket, $this->farMarket] as $market) {
            foreach (range(0, 6) as $day) {
                MarketSchedule::create([
                    'market_id' => $market->id,
                    'day_of_week' => $day,
                    'opening_time' => '08:00',
                    'closing_time' => '14:00',
                    'is_closed' => false,
                ]);
            }
        }
    }

    public function test_requires_valid_coordinates(): void
    {
        $this->getJson('/api/markets/nearby')->assertStatus(422);

        $this->getJson('/api/markets/nearby?latitude=999&longitude=67')->assertStatus(422);

        $this->getJson('/api/markets/nearby?latitude=24.9&longitude=abc')->assertStatus(422);
    }

    public function test_returns_nearby_markets_with_distance_sorted_nearest_first(): void
    {
        $response = $this->getJson('/api/markets/nearby?latitude=24.9000&longitude=67.0500&radius=30');

        $response->assertOk()
            ->assertJsonStructure([
                'user_location' => ['latitude', 'longitude', 'address'],
                'radius_km',
                'markets' => [['id', 'name', 'distance_km', 'url']],
            ])
            ->assertJsonPath('user_location.address', 'Test Address 1, Karachi, Pakistan')
            ->assertJsonPath('markets.0.name', 'Near Market');

        $markets = $response->json('markets');
        $this->assertCount(2, $markets);
        $this->assertLessThan($markets[1]['distance_km'], $markets[0]['distance_km']);
    }

    public function test_excludes_markets_outside_the_radius(): void
    {
        $response = $this->getJson('/api/markets/nearby?latitude=24.9000&longitude=67.0500&radius=10');

        $response->assertOk();
        $names = collect($response->json('markets'))->pluck('name');

        $this->assertContains('Near Market', $names);
        $this->assertNotContains('Far Market', $names);
    }

    public function test_includes_far_market_when_radius_is_widened(): void
    {
        $response = $this->getJson('/api/markets/nearby?latitude=24.9000&longitude=67.0500&radius=30');

        $names = collect($response->json('markets'))->pluck('name');

        $this->assertContains('Far Market', $names);
    }

    public function test_farmers_count_is_included_per_market(): void
    {
        $farmerUser = User::factory()->create(['role' => 'farmer']);
        $profile = FarmerProfile::create([
            'user_id' => $farmerUser->id,
            'business_name' => 'Test Farm',
            'contact_person' => 'Test Farmer',
            'address' => '1 Farm Road, Karachi',
            'approval_status' => 'approved',
        ]);
        FarmerMarket::create([
            'farmer_id' => $profile->id,
            'market_id' => $this->nearbyMarket->id,
            'status' => 'active',
        ]);

        $response = $this->getJson('/api/markets/nearby?latitude=24.9000&longitude=67.0500');

        $first = collect($response->json('markets'))->firstWhere('name', 'Near Market');
        $this->assertSame(1, $first['farmers_count']);
    }
}
