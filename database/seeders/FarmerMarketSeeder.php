<?php

namespace Database\Seeders;

use App\Models\FarmerMarket;
use App\Models\FarmerMarketSchedule;
use App\Models\FarmerProfile;
use App\Models\Market;
use App\Models\MarketSchedule;
use Illuminate\Database\Seeder;

class FarmerMarketSeeder extends Seeder
{
    public function run(): void
    {
        $markets = Market::all()->keyBy('name');

        // [farmer business, market name, stall name, stall location, [days present]]
        $assignments = [
            ['Greenfield Gardens', 'Green Valley Community Market', 'Greenfield Gardens Stall', 'Row A, Stalls 1-2', [6, 0]],
            ['Greenfield Gardens', 'Old Town Morning Market', 'Greenfield Veggies', 'North entrance', [3]],
            ['Orchard Lane Fruits', 'Green Valley Community Market', 'Orchard Lane Fruits', 'Row B, Stall 5', [6, 0]],
            ['Orchard Lane Fruits', 'Riverside Farmers Exchange', 'Orchard Lane', 'East pier', [2, 4]],
            ['Meadow Dairy', 'Green Valley Community Market', 'Meadow Dairy Cooler', 'Row A, Stall 8', [6]],
            ['Meadow Dairy', 'Riverside Farmers Exchange', 'Meadow Dairy', 'West pier', [4]],
            ['Hearth & Grain Bakery', 'Green Valley Community Market', 'Hearth & Grain', 'Center circle', [6, 0]],
            ['Hearth & Grain Bakery', 'Old Town Morning Market', 'Hearth & Grain', 'Clock tower', [3, 6]],
            // Karachi growers
            ['Indus Greens', 'Green Valley Community Market', 'Indus Greens Sabzi', 'Row C, Stalls 1-3', [6, 0]],
            ['Indus Greens', 'Old Town Morning Market', 'Indus Greens', 'East gate', [3]],
            ['Indus Greens', 'Port Gardens Weekend Market', 'Indus Greens', 'Garden row A', [5, 6]],
            ['Thar Honey Co.', 'Green Valley Community Market', 'Thar Honey Corner', 'Row B, Stall 9', [6]],
            ['Thar Honey Co.', 'Port Gardens Weekend Market', 'Thar Honey Corner', 'Garden row C', [5, 6]],
            ['Malir Date Farm', 'Riverside Farmers Exchange', 'Malir Dates & Chutney', 'West pier end', [2, 4]],
            ['Malir Date Farm', 'Port Gardens Weekend Market', 'Malir Dates', 'Garden row B', [5, 6]],
        ];

        foreach ($assignments as [$business, $marketName, $stallName, $stallLocation, $presentDays]) {
            $farmer = FarmerProfile::where('business_name', $business)->firstOrFail();
            $market = $markets[$marketName];

            // Idempotent by (farmer_id, market_id) so re-seeding never duplicates stalls.
            $fm = FarmerMarket::updateOrCreate(
                [
                    'farmer_id' => $farmer->id,
                    'market_id' => $market->id,
                ],
                [
                    'stall_name' => $stallName,
                    'stall_location' => $stallLocation,
                    'latitude' => $market->latitude + (random_int(-30, 30) / 10000),
                    'longitude' => $market->longitude + (random_int(-30, 30) / 10000),
                    'status' => 'active',
                ]
            );

            // Farmer is present on the days their market operates (from market schedules)
            foreach ($presentDays as $day) {
                $schedule = MarketSchedule::where('market_id', $market->id)->where('day_of_week', $day)->first();

                FarmerMarketSchedule::updateOrCreate(
                    [
                        'farmer_market_id' => $fm->id,
                        'day_of_week' => $day,
                    ],
                    [
                        'start_time' => $schedule?->opening_time ?? '08:00',
                        'end_time' => $schedule?->closing_time ?? '14:00',
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
