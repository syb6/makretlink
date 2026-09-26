<?php

namespace Database\Seeders;

use App\Models\CustomerProfile;
use App\Models\FavoriteFarmer;
use App\Models\FavoriteMarket;
use App\Models\FavoriteProduct;
use App\Models\Market;
use App\Models\Product;
use App\Models\FarmerProfile;
use Illuminate\Database\Seeder;

class FavoriteSeeder extends Seeder
{
    public function run(): void
    {
        $customers = CustomerProfile::all();
        $products = Product::where('status', 'active')->get();
        $farmers = FarmerProfile::where('approval_status', 'approved')->get();
        $markets = Market::where('status', 'active')->get();

        foreach ($customers as $customer) {
            foreach ($products->random(min(3, $products->count())) as $product) {
                FavoriteProduct::firstOrCreate([
                    'customer_id' => $customer->id,
                    'product_id' => $product->id,
                ], ['notify_on_restock' => true]);
            }

            foreach ($farmers->random(min(2, $farmers->count())) as $farmer) {
                FavoriteFarmer::firstOrCreate([
                    'customer_id' => $customer->id,
                    'farmer_id' => $farmer->id,
                ]);
            }

            foreach ($markets->random(min(1, $markets->count())) as $market) {
                FavoriteMarket::firstOrCreate([
                    'customer_id' => $customer->id,
                    'market_id' => $market->id,
                ]);
            }
        }
    }
}
