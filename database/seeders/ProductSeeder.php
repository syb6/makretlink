<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\FarmerProfile;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // Prices are in Rs (PKR) to match the Karachi setting.
        $catalog = [
            'Greenfield Gardens' => [
                ['Heirloom Tomatoes', 'Vegetables', 160.00, 'kg', 'Vine-ripened heirloom tomatoes in mixed colors. Bursting with flavor.'],
                ['Rainbow Carrots', 'Vegetables', 120.00, 'bunch', 'Sweet purple, orange and yellow carrots. Great roasted.'],
                ['Bell Pepper Trio', 'Vegetables', 190.00, 'pack', 'Red, yellow and green peppers, crisp and sweet.'],
                ['Cucumbers', 'Vegetables', 95.00, 'kg', 'Crunchy salad cucumbers picked this week.'],
                ['Farm Potatoes', 'Vegetables', 85.00, 'kg', 'All-rounder potatoes, perfect for salan and cutlets.'],
                ['Red Onions', 'Vegetables', 110.00, 'kg', 'Sharp, juicy onions cured for three weeks.'],
            ],
            'Orchard Lane Fruits' => [
                ['Kala Kulu Apples', 'Fruits', 340.00, 'kg', 'Crisp and juicy apples from our hillside orchard.'],
                ['White Peaches', 'Fruits', 420.00, 'kg', 'Tree-ripened white peaches. Fragrant and sweet.'],
                ['Blueberries', 'Fruits', 850.00, 'pint', 'Plump, antioxidant-rich blueberries picked at dawn.'],
                ['Cherries', 'Fruits', 1250.00, 'kg', 'Dark sweet cherries, a short-season treat.'],
                ['Sindhri Mangoes', 'Fruits', 280.00, 'kg', 'The king of mangoes — honey-sweet Sindhri, tree-ripened.'],
                ['Bananas (Sindhi)', 'Fruits', 190.00, 'dozen', 'Naturally ripened Sindhi bananas, creamy and sweet.'],
            ],
            'Meadow Dairy' => [
                ['Fresh Whole Milk', 'Dairy & Eggs', 220.00, 'liter', 'Creamy non-homogenized milk from grass-fed cows.'],
                ['Farmhouse Cheddar', 'Dairy & Eggs', 950.00, 'block', 'Aged 12 months. Sharp, crumbly, full of flavor.'],
                ['Greek Yoghurt', 'Dairy & Eggs', 280.00, 'tub', 'Thick, tangy yoghurt with nothing but milk and cultures.'],
                ['Free-Range Eggs', 'Dairy & Eggs', 340.00, 'dozen', 'Eggs from hens that roam the meadow all day.'],
                ['Desi Ghee', 'Dairy & Eggs', 2400.00, 'jar', 'Slow-cooked clarified butter, nutty and golden.'],
                ['Fresh Paneer', 'Dairy & Eggs', 520.00, 'pack', 'Soft, milky paneer set the same morning.'],
            ],
            'Hearth & Grain Bakery' => [
                ['Country Sourdough', 'Baked Goods', 380.00, 'loaf', 'Slow-fermented 24 hours. Crackling crust, open crumb.'],
                ['Butter Croissants (4pk)', 'Baked Goods', 520.00, 'pack', 'Laminated with real butter over three days.'],
                ['Cinnamon Rolls (6pk)', 'Baked Goods', 560.00, 'pack', 'Pillowy rolls with brown-butter glaze.'],
                ['Seeded Rye Loaf', 'Baked Goods', 400.00, 'loaf', 'Dense Nordic-style rye with sunflower and flax seeds.'],
                ['Whole Wheat Burger Buns (6pk)', 'Baked Goods', 300.00, 'pack', 'Soft, wholesome buns baked fresh every market day.'],
                ['Zeus Focaccia', 'Baked Goods', 450.00, 'loaf', 'Olive-oil rich focaccia with rosemary and sea salt.'],
            ],
            'Sprout House Microgreens' => [
                ['Pea Shoots', 'Herbs & Greens', 210.00, 'box', 'Sweet, crunchy pea shoots harvested to order.'],
                ['Basil Microgreens', 'Herbs & Greens', 240.00, 'box', 'Intense basil flavor in a tiny package.'],
                ['Rainbow Chard', 'Herbs & Greens', 140.00, 'bunch', 'Colorful stems, tender leaves. Great sautéed.'],
                ['Baby Spinach', 'Herbs & Greens', 90.00, 'bag', 'Tender baby spinach leaves, triple-washed and ready to eat.'],
                ['Fresh Coriander', 'Herbs & Greens', 40.00, 'bunch', 'Fragrant dhania cut the same morning.'],
                ['Fresh Mint', 'Herbs & Greens', 40.00, 'bunch', 'Cool podina for chutneys and chai.'],
            ],
            'Indus Greens' => [
                ['Seasonal Sabzi Mix', 'Vegetables', 130.00, 'kg', 'Whatever is best this week — a rotating mix of fresh sabzi.'],
                ['Okra (Bhindi)', 'Vegetables', 150.00, 'kg', 'Tender young okra, hand-picked at dawn.'],
                ['Brinjal (Baingan)', 'Vegetables', 110.00, 'kg', 'Glossy purple brinjal for bharta and curries.'],
                ['Cauliflower', 'Vegetables', 120.00, 'pc', 'Snow-white curds, farm fresh.'],
                ['Bitter Gourd (Karela)', 'Vegetables', 140.00, 'kg', 'Clean, crisp karela from river-fed beds.'],
                ['Green Chillies', 'Vegetables', 90.00, 'kg', 'Fiery green chillies, a kitchen essential.'],
            ],
            'Thar Honey Co.' => [
                ['Wild Thar Honey (500g)', 'Honey & Preserves', 1300.00, 'jar', 'Raw multi-floral honey from wild Thar hives. Nothing added.'],
                ['Wild Thar Honey (1kg)', 'Honey & Preserves', 2400.00, 'jar', 'Our flagship raw honey in a family-size jar.'],
                ['Acacia Honey (500g)', 'Honey & Preserves', 1500.00, 'jar', 'Light, delicate acacia honey — kids love it.'],
                ['Sun-Dried Fruit Preserve', 'Honey & Preserves', 480.00, 'jar', 'Traditional murabba made with Thar fruit and honey.'],
            ],
            'Malir Date Farm' => [
                ['Aseel Dates', 'Fruits', 720.00, 'kg', 'Karachi’s own Aseel dates — chewy, caramel-sweet.'],
                ['Dhakki Dates', 'Fruits', 980.00, 'kg', 'Premium large Dhakki dates, soft and rich.'],
                ['Date & Tamarind Chutney', 'Honey & Preserves', 450.00, 'jar', 'Sweet-tangy imli-khajoor chutney, a street-food classic.'],
                ['Mixed Fruit Jam', 'Honey & Preserves', 420.00, 'jar', 'Small-batch jam from Malir orchard fruit.'],
                ['Guavas', 'Fruits', 170.00, 'kg', 'Fragrant Sindhi guavas — sweet, musky, unbeatable.'],
            ],
            'Gadap Veggie House' => [
                ['Gadap Tomatoes', 'Vegetables', 100.00, 'kg', 'Karachi’s own tomato belt — ripe and ready.'],
                ['Lady Finger', 'Vegetables', 145.00, 'kg', 'Crisp, tender lady finger picked daily.'],
                ['Green Coriander Bundle', 'Herbs & Greens', 35.00, 'bunch', 'Fresh dhania from Gadap fields.'],
            ],
        ];

        $pendingFarmers = ['Sprout House Microgreens', 'Gadap Veggie House'];

        foreach ($catalog as $business => $products) {
            $farmer = FarmerProfile::where('business_name', $business)->firstOrFail();
            $isPending = in_array($business, $pendingFarmers, true);

            foreach ($products as [$name, $categoryName, $price, $unit, $description]) {
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
                        'status' => 'active',
                    ]
                );
            }
        }
    }
}
