<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Vegetables' => 'vegetables.png',
            'Fruits' => 'fruits.png',
            'Dairy & Eggs' => 'dairy-eggs.png',
            'Baked Goods' => 'baked-goods.png',
            'Herbs & Greens' => 'herbs-greens.png',
            'Honey & Preserves' => 'honey-preserves.png',
        ];

        foreach ($categories as $name => $image) {
            Category::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'status' => 'active', 'image' => $image]
            );
        }
    }
}
