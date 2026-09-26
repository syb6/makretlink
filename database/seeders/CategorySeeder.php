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
            'Vegetables' => '🥬',
            'Fruits' => '🍎',
            'Dairy & Eggs' => '🥛',
            'Baked Goods' => '🍞',
            'Herbs & Greens' => '🌿',
            'Honey & Preserves' => '🍯',
        ];

        foreach ($categories as $name => $emoji) {
            Category::create([
                'name' => $name,
                'slug' => Str::slug($name),
                'status' => 'active',
            ]);
        }
    }
}
