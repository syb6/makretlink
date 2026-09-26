<?php

namespace Database\Seeders;

use App\Models\FarmerMarket;
use App\Models\PickupSlot;
use App\Models\StockTemplate;
use App\Models\WeeklyStock;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class StockSeeder extends Seeder
{
    public function run(): void
    {
        $thisWeek = now()->startOfWeek(Carbon::SUNDAY);
        $nextWeek = $thisWeek->copy()->addWeek();

        $farmerMarkets = FarmerMarket::with(['farmer', 'market', 'schedules'])->get();

        foreach ($farmerMarkets as $fm) {
            $products = $fm->farmer->products()->get();

            foreach ($products as $index => $product) {
                // Some products are not stocked at some stalls
                if (random_int(1, 10) <= 2) {
                    continue;
                }

                $templateQty = match (true) {
                    str_contains($product->unit, 'loaf') || str_contains($product->unit, 'pack') => 20,
                    str_contains($product->unit, 'box') => 15,
                    default => random_int(25, 60),
                };

                // Recurring weekly stock template (the "recurring weekly stock" feature)
                StockTemplate::create([
                    'product_id' => $product->id,
                    'farmer_market_id' => $fm->id,
                    'default_quantity' => $templateQty,
                    'is_active' => true,
                ]);

                foreach ([$thisWeek, $nextWeek] as $weekStart) {
                    $qty = max(1, $templateQty + random_int(-10, 10));

                    // Leave this week's stock partly sold so numbers look realistic
                    $sold = $weekStart->isSameWeek(now(), Carbon::SUNDAY) ? random_int(0, max(1, (int) ($qty * 0.6))) : 0;
                    $available = max(0, $qty - $sold);

                    WeeklyStock::create([
                        'product_id' => $product->id,
                        'farmer_market_id' => $fm->id,
                        'stock_template_id' => null,
                        'week_start' => $weekStart->toDateString(),
                        'quantity' => $qty,
                        'available_quantity' => $available,
                        'status' => $available > 0 ? 'available' : 'sold_out',
                    ]);
                }
            }

            // Pickup slots for each day the farmer attends this market (this week + next week)
            foreach ($fm->schedules as $schedule) {
                foreach ([$thisWeek, $nextWeek] as $weekStart) {
                    $pickupDate = $weekStart->copy()->addDays($schedule->day_of_week);

                    if ($pickupDate->isPast() && ! $pickupDate->isToday()) {
                        continue;
                    }

                    $start = Carbon::parse($schedule->start_time);
                    $end = Carbon::parse($schedule->end_time);

                    // Two overlapping windows: morning + late
                    $mid = $start->copy()->addMinutes($start->diffInMinutes($end) / 2);

                    foreach ([[$start, $mid], [$mid, $end]] as [$s, $e]) {
                        $cutoff = $pickupDate->copy()->setTimeFrom($s)->subDay();

                        if ($cutoff->isPast()) {
                            $cutoff = now()->addHours(3);
                        }

                        PickupSlot::create([
                            'farmer_market_id' => $fm->id,
                            'pickup_date' => $pickupDate->toDateString(),
                            'start_time' => $s->format('H:i'),
                            'end_time' => $e->format('H:i'),
                            'cutoff_at' => $cutoff,
                            'max_orders' => random_int(10, 25),
                            'status' => 'active',
                        ]);
                    }
                }
            }
        }
    }
}
