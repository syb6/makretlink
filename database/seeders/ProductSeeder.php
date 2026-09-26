<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\FarmerProfile;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = [
            'Greenfield Gardens' => [
                ['Heirloom Tomatoes', 'Vegetables', 4.50, 'kg', 'Vine-ripened heirloom tomatoes in mixed colors. Bursting with flavor.'],
                ['Rainbow Carrots', 'Vegetables', 3.20, 'bunch', 'Sweet purple, orange and yellow carrots. Great roasted.'],
                ['Baby Spinach', 'Herbs & Greens', 3.80, 'bag', 'Tender baby spinach leaves, triple-washed and ready to eat.'],
                ['Bell Pepper Trio', 'Vegetables', 5.00, 'pack', 'Red, yellow and green peppers, crisp and sweet.'],
                ['Cucumbers', 'Vegetables', 2.40, 'kg', 'Crunchy salad cucumbers picked this week.'],
            ],
            'Orchard Lane Fruits' => [
                ['Honeycrisp Apples', 'Fruits', 5.50, 'kg', 'Crisp and juicy apples from our hillside orchard.'],
                ['White Peaches', 'Fruits', 6.80, 'kg', 'Tree-ripened white peaches. Fragrant and sweet.'],
                ['Blueberries', 'Fruits', 7.20, 'pint', 'Plump, antioxidant-rich blueberries picked at dawn.'],
                ['Cherries', 'Fruits', 9.50, 'kg', 'Dark sweet cherries, a short-season treat.'],
            ],
            'Meadow Dairy' => [
                ['Fresh Whole Milk', 'Dairy & Eggs', 3.50, 'liter', 'Creamy non-homogenized milk from grass-fed cows.'],
                ['Farmhouse Cheddar', 'Dairy & Eggs', 8.90, 'block', 'Aged 12 months. Sharp, crumbly, full of flavor.'],
                ['Greek Yoghurt', 'Dairy & Eggs', 4.60, 'tub', 'Thick, tangy yoghurt with nothing but milk and cultures.'],
                ['Free-Range Eggs', 'Dairy & Eggs', 5.20, 'dozen', 'Eggs from hens that roam the meadow all day.'],
            ],
            'Hearth & Grain Bakery' => [
                ['Country Sourdough', 'Baked Goods', 6.00, 'loaf', 'Slow-fermented 24 hours. Crackling crust, open crumb.'],
                ['Butter Croissants (4pk)', 'Baked Goods', 7.50, 'pack', 'Laminated with real butter over three days.'],
                ['Cinnamon Rolls (6pk)', 'Baked Goods', 8.20, 'pack', 'Pillowy rolls with brown-butter glaze.'],
                ['Seeded Rye Loaf', 'Baked Goods', 5.80, 'loaf', 'Dense Nordic-style rye with sunflower and flax seeds.'],
            ],
            'Sprout House Microgreens' => [
                ['Pea Shoots', 'Herbs & Greens', 3.90, 'box', 'Sweet, crunchy pea shoots harvested to order.'],
                ['Basil Microgreens', 'Herbs & Greens', 4.20, 'box', 'Intense basil flavor in a tiny package.'],
                ['Rainbow Chard', 'Herbs & Greens', 3.40, 'bunch', 'Colorful stems, tender leaves. Great sautéed.'],
            ],
        ];

        $pendingFarmer = 'Sprout House Microgreens'; // pending approval: still fine to seed, hidden publicly

        foreach ($catalog as $business => $products) {
            $farmer = FarmerProfile::where('business_name', $business)->firstOrFail();
            $isPending = $business === $pendingFarmer;

            foreach ($products as [$name, $categoryName, $price, $unit, $description]) {
                Product::create([
                    'farmer_id' => $farmer->id,
                    'category_id' => Category::where('name', $categoryName)->value('id'),
                    'name' => $name,
                    'slug' => \Illuminate\Support\Str::slug($name),
                    'description' => $description,
                    'price' => $price,
                    'unit' => $unit,
                    'status' => 'active',
                ]);
            }
        }
    }
}
