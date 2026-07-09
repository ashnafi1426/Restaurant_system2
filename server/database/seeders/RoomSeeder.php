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
            ['room_number' => '101', 'floor' => 1, 'status' => 'available', 'description' => 'Comfortable single room with city view'],
            ['room_number' => '102', 'floor' => 1, 'status' => 'available', 'description' => 'Spacious double room with balcony'],
            ['room_number' => '103', 'floor' => 1, 'status' => 'occupied', 'description' => 'Modern single room with workspace'],
            ['room_number' => '201', 'floor' => 2, 'status' => 'reserved', 'description' => 'Luxury suite with kitchen'],
            ['room_number' => '202', 'floor' => 2, 'status' => 'available', 'description' => 'Twin beds room with shared bathroom'],
            ['room_number' => '203', 'floor' => 2, 'status' => 'maintenance', 'description' => 'Deluxe room under renovation'],
            ['room_number' => '301', 'floor' => 3, 'status' => 'available', 'description' => 'Executive suite with lounge area'],
            ['room_number' => '302', 'floor' => 3, 'status' => 'available', 'description' => 'Standard room with garden view'],
        ];

        foreach ($rooms as $roomData) {
            Room::create([
                'id' => Str::uuid(),
                'room_number' => $roomData['room_number'],
                'room_type_id' => $roomTypes->random()->id,
                'floor' => $roomData['floor'],
                'status' => $roomData['status'],
                'description' => $roomData['description'],
                'is_active' => true,
            ]);
        }
    }
}
