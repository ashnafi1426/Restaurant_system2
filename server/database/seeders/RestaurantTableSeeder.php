<?php

namespace Database\Seeders;

use App\Models\RestaurantTable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * RestaurantTableSeeder
 * Seeds restaurant tables for walk-in customers
 */
class RestaurantTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tables = [];

        // Create 20 restaurant tables
        for ($i = 1; $i <= 20; $i++) {
            $tables[] = [
                'id' => Str::uuid(),
                'table_number' => sprintf('TABLE-%02d', $i),
                'qr_token' => 'qr_' . strtoupper(Str::random(12)),
                'capacity' => $i <= 5 ? 2 : ($i <= 10 ? 4 : 6),
                'status' => 'available',
                'assigned_waiter_id' => null,
                'location' => $this->getLocation($i),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Insert in chunks
        foreach (array_chunk($tables, 5) as $chunk) {
            RestaurantTable::insert($chunk);
        }

        echo "Restaurant tables seeded successfully!\n";
    }

    /**
     * Get table location by number
     */
    private function getLocation(int $tableNumber): string
    {
        $section = match (true) {
            $tableNumber <= 5 => 'Window Section',
            $tableNumber <= 10 => 'Main Dining Area',
            $tableNumber <= 15 => 'Bar Area',
            default => 'Private Section',
        };

        return $section;
    }
}
