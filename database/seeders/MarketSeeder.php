<?php

namespace Database\Seeders;

use App\Models\Market;
use App\Models\MarketSchedule;
use Illuminate\Database\Seeder;

class MarketSeeder extends Seeder
{
    public function run(): void
    {
        $markets = [
            [
                'name' => 'Green Valley Community Market',
                'address' => '100 Main Street, Green Valley',
                'latitude' => 40.741500,
                'longitude' => -73.987100,
                'description' => 'Our flagship Saturday market in the heart of Green Valley, with live music and 20+ stalls.',
                'days' => [6 => ['08:00', '14:00'], 0 => ['09:00', '13:00']], // Sat + Sun
            ],
            [
                'name' => 'Riverside Farmers Exchange',
                'address' => '55 Riverbank Road, Riverside',
                'latitude' => 40.728200,
                'longitude' => -74.002100,
                'description' => 'Weekday evening market along the river walk. Perfect for after-work shopping.',
                'days' => [2 => ['16:00', '20:00'], 4 => ['16:00', '20:00']], // Tue + Thu
            ],
            [
                'name' => 'Old Town Morning Market',
                'address' => '12 Heritage Square, Old Town',
                'latitude' => 40.719000,
                'longitude' => -73.995500,
                'description' => 'Early-bird market for the freshest picks. Coffee carts open from 7am.',
                'days' => [3 => ['07:00', '12:00'], 6 => ['07:00', '12:00']], // Wed + Sat
            ],
        ];

        foreach ($markets as $data) {
            $days = $data['days'];
            unset($data['days']);

            $market = Market::create($data + ['status' => 'active']);

            foreach (range(0, 6) as $d) {
                MarketSchedule::create([
                    'market_id' => $market->id,
                    'day_of_week' => $d,
                    'opening_time' => $days[$d][0] ?? null,
                    'closing_time' => $days[$d][1] ?? null,
                    'is_closed' => ! isset($days[$d]),
                ]);
            }
        }
    }
}
