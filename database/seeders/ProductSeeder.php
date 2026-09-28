<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\FarmerProfile;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Catalog cross-checked against public/images/placeholders/product-images/
     * — every product here has its own picture file, and the `image` column
     * stores the path (relative to public/images/placeholders/) so the UI can
     * render it straight from the DB.
     *
     * [name, category, price, unit, image file, description]
     */
    private array $catalog = [
        'Greenfield Gardens' => [
            ['Heirloom Tomatoes', 'Vegetables', 160.00, 'kg', 'heirloom-tomatoes.png', 'Vine-ripened heirloom tomatoes in mixed colors. Bursting with flavor.'],
            ['Rainbow Carrots', 'Vegetables', 120.00, 'bunch', 'rainbow carrot.png', 'Sweet purple, orange and yellow carrots. Great roasted.'],
            ['Bell Pepper Trio', 'Vegetables', 190.00, 'pack', 'bell-pepper-trio.png', 'Red, yellow and green peppers, crisp and sweet.'],
            ['Cucumbers', 'Vegetables', 95.00, 'kg', 'cucumbers.png', 'Crunchy salad cucumbers picked this week.'],
            ['Farm Potatoes', 'Vegetables', 85.00, 'kg', 'potatoes.png', 'All-rounder potatoes, perfect for salan and cutlets.'],
            ['Red Onions', 'Vegetables', 110.00, 'kg', 'red-onions.png', 'Sharp, juicy onions cured for three weeks.'],
        ],
        'Orchard Lane Fruits' => [
            ['Kala Kulu Apples', 'Fruits', 340.00, 'kg', 'apples.png', 'Crisp and juicy apples from our hillside orchard.'],
            ['White Peaches', 'Fruits', 420.00, 'kg', 'peaches.png', 'Tree-ripened white peaches. Fragrant and sweet.'],
            ['Blueberries', 'Fruits', 850.00, 'pint', 'blueberries.png', 'Plump, antioxidant-rich blueberries picked at dawn.'],
            ['Cherries', 'Fruits', 1250.00, 'kg', 'cherries.png', 'Dark sweet cherries, a short-season treat.'],
            ['Sindhri Mangoes', 'Fruits', 280.00, 'kg', 'mangoes-sindhri.png', 'The king of mangoes — honey-sweet Sindhri, tree-ripened.'],
            ['Bananas (Sindhi)', 'Fruits', 190.00, 'dozen', 'banana.png', 'Naturally ripened Sindhi bananas, creamy and sweet.'],
        ],
        'Meadow Dairy' => [
            ['Fresh Whole Milk', 'Dairy & Eggs', 220.00, 'liter', 'fresh-whole-milk.png', 'Creamy non-homogenized milk from grass-fed cows.'],
            ['Farmhouse Cheddar', 'Dairy & Eggs', 950.00, 'block', 'cheddar.png', 'Aged 12 months. Sharp, crumbly, full of flavor.'],
            ['Greek Yoghurt', 'Dairy & Eggs', 280.00, 'tub', 'greek-yogurt.png', 'Thick, tangy yoghurt with nothing but milk and cultures.'],
            ['Free-Range Eggs', 'Dairy & Eggs', 340.00, 'dozen', 'eggs.png', 'Eggs from hens that roam the meadow all day.'],
            ['Desi Ghee', 'Dairy & Eggs', 2400.00, 'jar', 'desi-ghee.png', 'Slow-cooked clarified butter, nutty and golden.'],
            ['Fresh Paneer', 'Dairy & Eggs', 520.00, 'pack', 'fresh-paneer.png', 'Soft, milky paneer set the same morning.'],
        ],
        'Hearth & Grain Bakery' => [
            ['Country Sourdough', 'Baked Goods', 380.00, 'loaf', 'country-sourdough.png', 'Slow-fermented 24 hours. Crackling crust, open crumb.'],
            ['Butter Croissants (4pk)', 'Baked Goods', 520.00, 'pack', 'butter-croisant.png', 'Laminated with real butter over three days.'],
            ['Cinnamon Rolls (6pk)', 'Baked Goods', 560.00, 'pack', 'cinnamon-roll.png', 'Pillowy rolls with brown-butter glaze.'],
            ['Seeded Rye Loaf', 'Baked Goods', 400.00, 'loaf', 'seeded-rye-loaf.png', 'Dense Nordic-style rye with sunflower and flax seeds.'],
            ['Whole Wheat Burger Buns (6pk)', 'Baked Goods', 300.00, 'pack', 'whole-wheat-burger-bun.png', 'Soft, wholesome buns baked fresh every market day.'],
            ['Zeus Focaccia', 'Baked Goods', 450.00, 'loaf', 'zeus focaccia.png', 'Olive-oil rich focaccia with rosemary and sea salt.'],
        ],
        'Sprout House Microgreens' => [
            ['Pea Shoots', 'Herbs & Greens', 210.00, 'box', 'pea-shoots.png', 'Sweet, crunchy pea shoots harvested to order.'],
        ],
    ];

    public function run(): void
    {
        // Prices are in Rs (PKR) to match the Karachi setting.
        $pendingFarmers = ['Sprout House Microgreens'];

        foreach ($this->catalog as $business => $products) {
            $farmer = FarmerProfile::where('business_name', $business)->firstOrFail();
            $isPending = in_array($business, $pendingFarmers, true);

            foreach ($products as [$name, $categoryName, $price, $unit, $image, $description]) {
                Product::updateOrCreate(
                    [
                        'farmer_id' => $farmer->id,
                        'slug' => Str::slug($name),
                    ],
                    [
                        'category_id' => Category::where('name', $categoryName)->value('id'),
                        'name' => $name,
                        'description' => $description,
                        'price' => $price,
                        'unit' => $unit,
                        'image' => 'product-images/'.$image,
                        'status' => 'active',
                    ]
                );
            }
        }
    }
}
