<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            CategorySeeder::class,
            MarketSeeder::class,
            FarmerMarketSeeder::class,
            ProductSeeder::class,
            StockSeeder::class,
            OrderSeeder::class,
            ReviewSeeder::class,
            FavoriteSeeder::class,
            AnnouncementSeeder::class,
            NotificationSeeder::class,
        ]);
    }
}
