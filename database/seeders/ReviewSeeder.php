<?php

namespace Database\Seeders;

use App\Models\FarmerReview;
use App\Models\Order;
use App\Models\ProductReview;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        $comments = [
            5 => ['Absolutely wonderful — freshest I have ever bought!', 'Exceeded expectations, will reorder for sure.', 'Perfect quality and lovely service.'],
            4 => ['Very good quality, slightly small portions.', 'Happy with it, will buy again.', 'Great taste, packaging could improve.'],
            3 => ['Decent but nothing special.', 'Average quality for the price.'],
            2 => ['Not as fresh as expected.', 'A bit disappointing this week.'],
        ];

        $farmerReplies = [
            'Thank you so much! See you at the market this weekend 🌱',
            'We really appreciate the feedback — the whole family works hard on this.',
            'Sorry to hear that! Message us and we will make it right next pickup.',
        ];

        $completedOrders = Order::with(['items', 'farmerMarket.farmer', 'customer'])
            ->where('status', 'completed')
            ->get();

        foreach ($completedOrders as $order) {
            if (! $order->farmerMarket || ! $order->customer) {
                continue;
            }

            // Farmer review for most completed orders
            if (random_int(1, 10) <= 8) {
                $rating = random_int(4, 5);

                FarmerReview::create([
                    'customer_id' => $order->customer_id,
                    'farmer_id' => $order->farmerMarket->farmer_id,
                    'order_id' => $order->id,
                    'rating' => $rating,
                    'comment' => collect($comments[$rating])->random(),
                    'farmer_reply' => random_int(1, 10) <= 6 ? collect($farmerReplies)->random() : null,
                    'replied_at' => now()->subDays(random_int(1, 5)),
                    'status' => 'visible',
                    'created_at' => $order->completed_at ?? now(),
                ]);
            }

            // Product reviews for some items
            foreach ($order->items->random(min(1, $order->items->count())) as $item) {
                if (! $item->product_id || random_int(1, 10) <= 4) {
                    continue;
                }

                $rating = random_int(3, 5);

                ProductReview::create([
                    'customer_id' => $order->customer_id,
                    'product_id' => $item->product_id,
                    'order_id' => $order->id,
                    'rating' => $rating,
                    'comment' => collect($comments[$rating])->random(),
                    'status' => 'visible',
                    'created_at' => $order->completed_at ?? now(),
                ]);
            }
        }
    }
}
