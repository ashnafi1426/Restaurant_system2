<?php

namespace Database\Seeders;

use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        $roomTypes = RoomType::all();

        if ($roomTypes->isEmpty()) {
            $this->call(RoomTypeSeeder::class);
            $roomTypes = RoomType::all();
        }

        $rooms = [
            ['room_number' => '101', 'floor' => 1, 'status' => 'available', 'description' => 'Comfortable single room with city view', 'type_index' => 0], // Standard
            ['room_number' => '102', 'floor' => 1, 'status' => 'available', 'description' => 'Spacious double room with balcony', 'type_index' => 1], // Deluxe
            ['room_number' => '103', 'floor' => 1, 'status' => 'occupied', 'description' => 'Modern single room with workspace', 'type_index' => 0], // Standard
            ['room_number' => '201', 'floor' => 2, 'status' => 'reserved', 'description' => 'Luxury suite with kitchen', 'type_index' => 2], // Suite
            ['room_number' => '202', 'floor' => 2, 'status' => 'available', 'description' => 'Twin beds room with shared bathroom', 'type_index' => 1], // Deluxe
            ['room_number' => '203', 'floor' => 2, 'status' => 'maintenance', 'description' => 'Deluxe room under renovation', 'type_index' => 1], // Deluxe
            ['room_number' => '301', 'floor' => 3, 'status' => 'available', 'description' => 'Executive suite with lounge area', 'type_index' => 3], // Executive Suite
            ['room_number' => '302', 'floor' => 3, 'status' => 'available', 'description' => 'Standard room with garden view', 'type_index' => 0], // Standard
            ['room_number' => '303', 'floor' => 3, 'status' => 'available', 'description' => 'Premium suite with spa', 'type_index' => 2], // Suite
        ];

        $roomTypesArray = $roomTypes->toArray();

        foreach ($rooms as $roomData) {
            $typeIndex = $roomData['type_index'] ?? 0;
            $roomType = isset($roomTypesArray[$typeIndex]) ? $roomTypesArray[$typeIndex] : $roomTypesArray[0];
            
            Room::create([
                'id' => Str::uuid(),
                'room_number' => $roomData['room_number'],
                'room_type_id' => $roomType['id'],
                'floor' => $roomData['floor'],
                'status' => $roomData['status'],
                'description' => $roomData['description'],
                'is_active' => true,
            ]);
        }
    }
}
