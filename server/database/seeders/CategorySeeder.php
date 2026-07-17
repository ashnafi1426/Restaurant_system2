<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Breakfast',
                'slug' => 'breakfast',
                'icon' => '☀️',
                'description' => 'Morning delicacies to start your day',
                'display_order' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Lunch',
                'slug' => 'lunch',
                'icon' => '🍔',
                'description' => 'Midday meals and light bites',
                'display_order' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Dinner',
                'slug' => 'dinner',
                'icon' => '🍲',
                'description' => 'Evening main courses and specials',
                'display_order' => 3,
                'is_active' => true,
            ],
            [
                'name' => 'Appetizers',
                'slug' => 'appetizers',
                'icon' => '🥗',
                'description' => 'Starters and small plates',
                'display_order' => 4,
                'is_active' => true,
            ],
            [
                'name' => 'Pizza',
                'slug' => 'pizza',
                'icon' => '🍕',
                'description' => 'Traditional and specialty pizzas',
                'display_order' => 5,
                'is_active' => true,
            ],
            [
                'name' => 'Pasta',
                'slug' => 'pasta',
                'icon' => '🍝',
                'description' => 'Italian pasta dishes',
                'display_order' => 6,
                'is_active' => true,
            ],
            [
                'name' => 'Desserts',
                'slug' => 'desserts',
                'icon' => '🍰',
                'description' => 'Sweet treats and desserts',
                'display_order' => 7,
                'is_active' => true,
            ],
            [
                'name' => 'Drinks',
                'slug' => 'drinks',
                'icon' => '🍹',
                'description' => 'Beverages and beverages',
                'display_order' => 8,
                'is_active' => true,
            ],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }
    }
}
