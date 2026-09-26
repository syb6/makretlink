<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\User;
use App\Notifications\OrderStatusUpdate;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    /**
     * Replays real order statuses as in-app notifications for the demo users
     * (skipping the actor who caused the change, exactly like production flow).
     */
    public function run(): void
    {
        $orders = Order::with(['customer.user', 'farmerMarket.farmer.user'])
            ->whereIn('status', ['placed', 'accepted', 'ready_for_pickup', 'completed'])
            ->get();

        foreach ($orders as $order) {
            $farmerUser = $order->farmerMarket?->farmer?->user;

            // New orders ring the farmer's bell
            if (in_array($order->status, ['placed'], true) && $farmerUser) {
                $farmerUser->notify(new OrderStatusUpdate($order, 'placed', 'New pre-order received.', 'farmer'));
                continue;
            }

            // Accepted / ready / completed go to the customer
            if ($order->customer?->user) {
                $order->customer->user->notify(
                    new OrderStatusUpdate($order, $order->status, null, 'customer')
                );
            }

            // Completed orders also notify the farmer (sale closed)
            if ($order->status === 'completed' && $farmerUser) {
                $farmerUser->notify(new OrderStatusUpdate($order, 'completed', null, 'farmer'));
            }
        }
    }
}
