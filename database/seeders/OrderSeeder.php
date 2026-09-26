<?php

namespace Database\Seeders;

use App\Models\CustomerProfile;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PickupSlot;
use App\Models\WeeklyStock;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $customers = CustomerProfile::with('user')->get();
        $slots = PickupSlot::with('farmerMarket.farmer', 'farmerMarket.market')->get();

        if ($slots->isEmpty() || $customers->isEmpty()) {
            return;
        }

        // Scenarios: mostly past/completed, a few placed/accepted in the future
        $scenarios = [
            ['status' => 'completed', 'daysOffset' => -21, 'count' => 3],
            ['status' => 'completed', 'daysOffset' => -7, 'count' => 3],
            ['status' => 'cancelled', 'daysOffset' => -10, 'count' => 1],
            ['status' => 'declined', 'daysOffset' => -5, 'count' => 1],
            ['status' => 'ready_for_pickup', 'daysOffset' => 0, 'count' => 2],
            ['status' => 'accepted', 'daysOffset' => 2, 'count' => 2],
            ['status' => 'placed', 'daysOffset' => 3, 'count' => 3],
        ];

        foreach ($scenarios as $scenario) {
            for ($i = 0; $i < $scenario['count']; $i++) {
                $slot = $slots->random();
                $customer = $customers->random();
                $fm = $slot->farmerMarket;

                $placedAt = now()->addDays($scenario['daysOffset'])->subDays(random_int(1, 4));

                // Pick 1-3 products stocked by this farmer at this stall
                $stocks = WeeklyStock::where('farmer_market_id', $fm->id)
                    ->where('status', 'available')
                    ->where('available_quantity', '>', 0)
                    ->with('product')
                    ->inRandomOrder()
                    ->take(random_int(1, 3))
                    ->get();

                if ($stocks->isEmpty()) {
                    continue;
                }

                $items = $stocks->map(fn ($s) => [
                    'product' => $s->product,
                    'quantity' => random_int(1, 3),
                    'price' => (float) $s->product->price,
                ]);

                $total = $items->sum(fn ($it) => $it['quantity'] * $it['price']);

                /** @var Order $order */
                $order = Order::create([
                    'order_number' => Order::generateOrderNumber(),
                    'customer_id' => $customer->id,
                    'farmer_market_id' => $fm->id,
                    'pickup_slot_id' => $slot->id,
                    'customer_name' => $customer->user->name,
                    'customer_email' => $customer->user->email,
                    'customer_phone' => $customer->user->phone,
                    'farmer_name' => $fm->farmer->business_name,
                    'market_name' => $fm->market->name,
                    'pickup_date' => $slot->pickup_date,
                    'pickup_start_time' => $slot->start_time,
                    'pickup_end_time' => $slot->end_time,
                    'status' => $scenario['status'],
                    'total_amount' => $total,
                    'customer_note' => random_int(1, 4) === 1 ? 'Please choose the ripest ones. Thank you!' : null,
                    'placed_at' => $placedAt,
                ]);

                foreach ($items as $it) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $it['product']->id,
                        'product_name' => $it['product']->name,
                        'unit' => $it['product']->unit,
                        'quantity' => $it['quantity'],
                        'unit_price' => $it['price'],
                        'subtotal' => $it['quantity'] * $it['price'],
                    ]);
                }

                // Status history trail
                $trail = match ($scenario['status']) {
                    'placed' => ['placed'],
                    'accepted' => ['placed', 'accepted'],
                    'ready_for_pickup' => ['placed', 'accepted', 'ready_for_pickup'],
                    'completed' => ['placed', 'accepted', 'ready_for_pickup', 'completed'],
                    'declined' => ['placed', 'declined'],
                    'cancelled' => ['placed', 'accepted', 'cancelled'],
                };

                foreach ($trail as $idx => $status) {
                    $order->statusHistories()->create([
                        'status' => $status,
                        'changed_by' => $status === 'placed' ? $customer->user->id : $fm->farmer->user_id,
                        'note' => match ($status) {
                            'placed' => 'Order placed by customer.',
                            'accepted' => 'Accepted by farmer.',
                            'ready_for_pickup' => 'Packed and ready for pickup.',
                            'completed' => 'Picked up and paid in person.',
                            'declined' => 'Farmer could not fulfil this week.',
                            'cancelled' => 'Cancelled before cutoff.',
                        },
                        'changed_at' => $placedAt->copy()->addHours($idx * 6),
                    ]);
                }

                // Timestamps on the order itself
                $order->update([
                    'accepted_at' => in_array($scenario['status'], ['accepted', 'ready_for_pickup', 'completed', 'cancelled']) ? $placedAt->copy()->addHours(6) : null,
                    'ready_at' => in_array($scenario['status'], ['ready_for_pickup', 'completed']) ? $placedAt->copy()->addHours(12) : null,
                    'completed_at' => $scenario['status'] === 'completed' ? $placedAt->copy()->addHours(30) : null,
                    'cancelled_at' => in_array($scenario['status'], ['cancelled']) ? $placedAt->copy()->addHours(10) : null,
                ]);
            }
        }
    }
}
