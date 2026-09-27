<?php

namespace Database\Seeders;

use App\Models\Market;
use App\Models\MarketSchedule;
use Illuminate\Database\Seeder;

class MarketSeeder extends Seeder
{
    /**
     * Markets are seeded at real Karachi locations (the operator's city)
     * so the geolocation "near me" feature has believable data.
     *
     * Names are kept stable — other seeders (Farmers, Favorites, Stock)
     * reference them by name. updateOrCreate keeps re-seeding idempotent.
     */
    public function run(): void
    {
        $markets = [
            [
                'name' => 'Green Valley Community Market',
                'address' => 'University Road, Gulshan-e-Iqbal, Karachi',
                'latitude' => 24.9197000,
                'longitude' => 67.0990000,
                'description' => 'Our flagship Saturday market in the heart of Gulshan-e-Iqbal, with live music and 20+ stalls.',
                'days' => [6 => ['08:00', '14:00'], 0 => ['09:00', '13:00']], // Sat + Sun
            ],
            [
                'name' => 'Riverside Farmers Exchange',
                'address' => 'Boat Basin, Clifton, Karachi',
                'latitude' => 24.8100000,
                'longitude' => 67.0300000,
                'description' => 'Weekday evening market along the Boat Basin food street. Perfect for after-work shopping.',
                'days' => [2 => ['16:00', '20:00'], 4 => ['16:00', '20:00']], // Tue + Thu
            ],
            [
                'name' => 'Old Town Morning Market',
                'address' => 'Empress Market, Saddar, Karachi',
                'latitude' => 24.8560000,
                'longitude' => 67.0290000,
                'description' => 'Early-bird market for the freshest picks. Coffee carts open from 7am.',
                'days' => [3 => ['07:00', '12:00'], 6 => ['07:00', '12:00']], // Wed + Sat
            ],
            [
                'name' => 'Port Gardens Weekend Market',
                'address' => 'KPT Gardens, Keamari, Karachi',
                'latitude' => 24.8430000,
                'longitude' => 66.9890000,
                'description' => 'Harbour-side weekend market with fresh catches, dates and desserts.',
                'days' => [5 => ['15:00', '21:00'], 6 => ['10:00', '18:00']], // Fri + Sat
            ],
        ];

        foreach ($markets as $data) {
            $days = $data['days'];
            unset($data['days']);

            $market = Market::withTrashed()->updateOrCreate(
                ['name' => $data['name']],
                $data + ['status' => 'active']
            );

            if ($market->trashed()) {
                $market->restore();
            }

            foreach (range(0, 6) as $d) {
                MarketSchedule::updateOrCreate(
                    [
                        'market_id' => $market->id,
                        'day_of_week' => $d,
                    ],
                    [
                        'opening_time' => $days[$d][0] ?? null,
                        'closing_time' => $days[$d][1] ?? null,
                        'is_closed' => ! isset($days[$d]),
                    ]
                );
            }
        }
    }
}
