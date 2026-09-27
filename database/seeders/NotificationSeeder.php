<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Notifications\OrderStatusUpdate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Notification;

class NotificationSeeder extends Seeder
{
    /**
     * Replays real order statuses as in-app notifications for the demo users
     * (skipping the actor who caused the change, exactly like production flow).
     *
     * Database channel only: seeding must never depend on a working mail
     * transport (in production the @marketlink.test demo mailboxes don't
     * exist; under Resend's sandbox sender they are rejected outright).
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
                Notification::sendNow(
                    $farmerUser,
                    new OrderStatusUpdate($order, 'placed', 'New pre-order received.', 'farmer'),
                    ['database']
                );

                continue;
            }

            // Accepted / ready / completed go to the customer
            if ($order->customer?->user) {
                Notification::sendNow(
                    $order->customer->user,
                    new OrderStatusUpdate($order, $order->status, null, 'customer'),
                    ['database']
                );
            }

            // Completed orders also notify the farmer (sale closed)
            if ($order->status === 'completed' && $farmerUser) {
                Notification::sendNow(
                    $farmerUser,
                    new OrderStatusUpdate($order, 'completed', null, 'farmer'),
                    ['database']
                );
            }
        }
    }
}
