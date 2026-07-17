<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MenuItem;
use Illuminate\Support\Str;

class AppetizerMenuItemSeeder extends Seeder
{
    public function run(): void
    {
        $appetizers = [
            [
                'name' => 'Cold Appetizer',
                'description' => 'Delicious cold appetizer with fresh ingredients',
                'price' => 300.00,
                'image' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQVnK2F0pp8N6OO-icvSfJ4vMGXk57f_A__EtlkfJtkBQ&s=10',
            ],
            [
                'name' => 'Appetizer Platter',
                'description' => 'Mixed appetizers with vegetables and dips',
                'price' => 450.00,
                'image' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcT3VXvY-p1QZ5oK9qL7mN8rP2sT4uVwXyZ1aB&s=10',
            ],
            [
                'name' => 'Cheese & Herb Appetizer',
                'description' => 'Fresh cheese with herbs and crackers',
                'price' => 250.00,
                'image' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcS5gH9kJ3mL5nOpQrStUvWxYzAbCdEfGhIjKl&s=10',
            ],
        ];

        foreach ($appetizers as $appetizer) {
            MenuItem::create([
                'id' => Str::uuid(),
                'name' => $appetizer['name'],
                'description' => $appetizer['description'],
                'category' => 'appetizers',
                'price' => $appetizer['price'],
                'image' => $appetizer['image'],
                'is_available' => true,
            ]);
            echo "✅ Created: {$appetizer['name']}\n";
        }

        echo "\n✅ Appetizer menu items seeded successfully!\n";
    }
}
