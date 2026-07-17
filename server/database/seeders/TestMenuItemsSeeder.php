<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MenuItem;
use Illuminate\Support\Str;

class TestMenuItemsSeeder extends Seeder
{
    public function run(): void
    {
        $menuItems = [
            // Breakfast items
            [
                'name' => 'Fluffy Pancakes',
                'description' => 'Stack of fluffy pancakes with maple syrup and butter',
                'category' => 'breakfast',
                'price' => 280.00,
                'image' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQcKU2M3d0unj_i0XxBr2jFiU5qC2bxczkdBg5LvCXsfdzGfQaF',
            ],
            [
                'name' => 'Eggs Benedict',
                'description' => 'Poached eggs with English muffin and hollandaise sauce',
                'category' => 'breakfast',
                'price' => 350.00,
                'image' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcS8vK3l4mN5oP6qR7sT8uV9wX0yZ1aB2cD3eF&s=10',
            ],
            
            // Lunch items
            [
                'name' => 'Grilled Chicken Burger',
                'description' => 'Juicy grilled chicken patty with lettuce, tomato, and special sauce',
                'category' => 'lunch',
                'price' => 320.00,
                'image' => 'https://www.targulcartii.ro/galerie/cache/WI016/cinzia-trenchi-burgeri-creative-publishing-2016-l-866770-480x760.JPG',
            ],
            [
                'name' => 'Caesar Salad',
                'description' => 'Fresh romaine lettuce with parmesan cheese and croutons',
                'category' => 'lunch',
                'price' => 280.00,
                'image' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcT2vL4mM5nO6pQ7rS8tU9vW0xX1yZ2aB3cD4eF&s=10',
            ],
            
            // Dinner items
            [
                'name' => 'Grilled Salmon',
                'description' => 'Pan-seared salmon fillet with lemon butter sauce and seasonal vegetables',
                'category' => 'dinner',
                'price' => 520.00,
                'image' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcU3wM5nO6pQ7rS8tU9vW0xX1yZ2aB3cD4eF5fG&s=10',
            ],
            [
                'name' => 'Filet Mignon',
                'description' => 'Premium cut tenderloin steak with truffle mashed potatoes',
                'category' => 'dinner',
                'price' => 680.00,
                'image' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcV4xN6oP7qR8sT9uV0wX1yY2zA3bB4cC5dD6eE&s=10',
            ],
            
            // Desserts
            [
                'name' => 'Chocolate Lava Cake',
                'description' => 'Warm chocolate cake with molten center and vanilla ice cream',
                'category' => 'dessert',
                'price' => 220.00,
                'image' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcW5yO7pQ8rR9sT0uU1vV2wW3xX4yY5zZ0aA1bB&s=10',
            ],
            [
                'name' => 'Cheesecake',
                'description' => 'New York style cheesecake with strawberry topping',
                'category' => 'dessert',
                'price' => 250.00,
                'image' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcX6zP8qR9sS0tT1uU2vV3wW4xX5yY6zZ0aA1bB&s=10',
            ],
            
            // Drinks
            [
                'name' => 'Fresh Orange Juice',
                'description' => 'Freshly squeezed orange juice',
                'category' => 'drinks',
                'price' => 120.00,
                'image' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcY7aQ9rS0tT1uU2vV3wW4xX5yY6zZ0aA1bB2cC&s=10',
            ],
            [
                'name' => 'Iced Coffee',
                'description' => 'Cold brew coffee with ice and milk',
                'category' => 'drinks',
                'price' => 150.00,
                'image' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcZ8bR0sT1uU2vV3wW4xX5yY6zZ0aA1bB2cC3dD&s=10',
            ],
        ];

        foreach ($menuItems as $item) {
            MenuItem::create([
                'id' => Str::uuid(),
                'name' => $item['name'],
                'description' => $item['description'],
                'category' => $item['category'],
                'price' => $item['price'],
                'image' => $item['image'],
                'is_available' => true,
            ]);

            echo "✅ Created: {$item['name']} ({$item['category']})\n";
        }

        echo "\n✅ Test menu items seeded successfully!\n";
    }
}
