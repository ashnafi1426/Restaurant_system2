<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RestaurantTable;
use Illuminate\Support\Str;

class RestaurantTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Creates sample restaurant tables for testing
     */
    public function run(): void
    {
        $tables = [
            // Main Dining Area
            ['table_number' => 'T01', 'table_name' => 'Window Table 1', 'capacity' => 2, 'location' => 'Main Dining'],
            ['table_number' => 'T02', 'table_name' => 'Window Table 2', 'capacity' => 2, 'location' => 'Main Dining'],
            ['table_number' => 'T03', 'table_name' => 'Corner Booth', 'capacity' => 4, 'location' => 'Main Dining'],
            ['table_number' => 'T04', 'table_name' => 'Center Table 1', 'capacity' => 4, 'location' => 'Main Dining'],
            ['table_number' => 'T05', 'table_name' => 'Center Table 2', 'capacity' => 4, 'location' => 'Main Dining'],
            ['table_number' => 'T06', 'table_name' => 'Large Family Table', 'capacity' => 6, 'location' => 'Main Dining'],
            ['table_number' => 'T07', 'table_name' => 'Main Dining Table 7', 'capacity' => 4, 'location' => 'Main Dining'],
            ['table_number' => 'T08', 'table_name' => 'Main Dining Table 8', 'capacity' => 4, 'location' => 'Main Dining'],

            // Terrace/Outdoor
            ['table_number' => 'T09', 'table_name' => 'Terrace Table 1', 'capacity' => 2, 'location' => 'Terrace'],
            ['table_number' => 'T10', 'table_name' => 'Terrace Table 2', 'capacity' => 2, 'location' => 'Terrace'],
            ['table_number' => 'T11', 'table_name' => 'Terrace Booth', 'capacity' => 4, 'location' => 'Terrace'],
            ['table_number' => 'T12', 'table_name' => 'Terrace Large Table', 'capacity' => 6, 'location' => 'Terrace'],

            // Private Dining / VIP
            ['table_number' => 'V01', 'table_name' => 'VIP Private Room 1', 'capacity' => 8, 'location' => 'Private Dining'],
            ['table_number' => 'V02', 'table_name' => 'VIP Private Room 2', 'capacity' => 10, 'location' => 'Private Dining'],

            // Bar Area
            ['table_number' => 'B01', 'table_name' => 'Bar Table 1', 'capacity' => 2, 'location' => 'Bar'],
            ['table_number' => 'B02', 'table_name' => 'Bar Table 2', 'capacity' => 2, 'location' => 'Bar'],
            ['table_number' => 'B03', 'table_name' => 'Bar High Table', 'capacity' => 4, 'location' => 'Bar'],
        ];

        $hotel = \App\Models\Hotel::first();
        foreach ($tables as $tableData) {
            RestaurantTable::firstOrCreate(
                ['table_number' => $tableData['table_number'], 'hotel_id' => $hotel?->id],
                [
                    'table_name' => $tableData['table_name'],
                    'capacity' => $tableData['capacity'],
                    'location' => $tableData['location'],
                    'status' => RestaurantTable::STATUS_AVAILABLE,
                    'is_active' => true,
                ]
            );

            $this->command->info("Created table: {$tableData['table_number']} - {$tableData['table_name']}");
        }

        $this->command->info(' Restaurant tables seeded successfully!');
        $this->command->info('Total tables created: ' . count($tables));
    }
}
