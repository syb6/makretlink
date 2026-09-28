<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        // Each category's picture file inside
        // public/images/placeholders/category-images/ — the path is stored
        // in the `image` column and rendered by Category::image_url.
        $categories = [
            'Vegetables' => 'vegetables.png',
            'Fruits' => 'fruits.png',
            'Dairy & Eggs' => 'dairy.png',
            'Baked Goods' => 'baked-goods.png',
            'Herbs & Greens' => 'herbs-and-greens.png',
            'Honey & Preserves' => 'honey-preservatives.png',
        ];

        foreach ($categories as $name => $image) {
            Category::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'status' => 'active', 'image' => 'category-images/'.$image]
            );
        }
    }
}
