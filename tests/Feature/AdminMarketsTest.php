<?php

namespace Tests\Feature;

use App\Models\Market;
use App\Models\MarketSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesMarketLinkData;
use Tests\TestCase;

class AdminMarketsTest extends TestCase
{
    use CreatesMarketLinkData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMarketLink();
    }

    public function test_admin_market_listing_includes_schedules_stall_counts_and_pagination(): void
    {
        $market = Market::create([
            'name' => 'A Market',
            'address' => '1 Market Road',
            'latitude' => 24.8607,
            'longitude' => 67.0011,
            'status' => 'active',
        ]);
        MarketSchedule::create([
            'market_id' => $market->id,
            'day_of_week' => 6,
            'opening_time' => '08:00',
            'closing_time' => '14:00',
            'is_closed' => false,
        ]);

        $this->actAsAdmin()
            ->getJson('/api/admin/markets')
            ->assertOk()
            ->assertJsonPath('total', 2)
            ->assertJsonPath('page', 1)
            ->assertJsonPath('last_page', 1)
            ->assertJsonPath('rows.0.name', 'A Market')
            ->assertJsonPath('rows.0.latitude', 24.8607)
            ->assertJsonPath('rows.0.longitude', 67.0011)
            ->assertJsonPath('rows.0.farmer_markets_count', 0)
            ->assertJsonPath('rows.0.schedules.0.day_of_week', 6)
            ->assertJsonPath('rows.0.schedules.0.is_closed', false)
            ->assertJsonPath('rows.1.name', 'Test Market')
            ->assertJsonPath('rows.1.farmer_markets_count', 1);
    }

    public function test_admin_market_schedule_crud_preserves_local_wall_clock_times(): void
    {
        $this->actAsAdmin()
            ->postJson('/api/admin/markets', [
                'name' => 'Schedule Test Market',
                'address' => '1 Schedule Road',
                'status' => 'active',
                'days' => [6 => ['open' => '09:00', 'close' => '17:00']],
            ])
            ->assertOk();

        $market = Market::where('name', 'Schedule Test Market')->firstOrFail();

        $schedule = MarketSchedule::where('market_id', $market->id)->where('day_of_week', 6)->firstOrFail();
        $this->assertSame('09:00', $schedule->opening_time->format('H:i'));
        $this->assertSame('17:00', $schedule->closing_time->format('H:i'));
        $this->assertFalse($schedule->is_closed);

        $this->postJson("/api/admin/markets/{$market->id}", [
            'name' => 'Schedule Test Market',
            'address' => '1 Schedule Road',
            'status' => 'active',
            'days' => [6 => ['open' => '10:00', 'close' => '18:00']],
        ])->assertOk();

        $schedule->refresh();
        $this->assertSame('10:00', $schedule->opening_time->format('H:i'));
        $this->assertSame('18:00', $schedule->closing_time->format('H:i'));
        $this->assertFalse($schedule->is_closed);
    }
}
