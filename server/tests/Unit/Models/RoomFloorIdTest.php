<?php

namespace Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use App\Models\Room;
use App\Models\HotelFloor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase as BaseTestCase;

class RoomFloorIdTest extends BaseTestCase
{
    use RefreshDatabase;

    public function test_room_has_floor_id_field()
    {
        // Create a hotel floor
        $floor = HotelFloor::create([
            'floor_number' => 1,
            'name' => 'Ground Floor',
            'description' => 'Ground floor test',
            'is_active' => true,
            'total_rooms' => 10,
        ]);

        // Create a room type (required for room creation)
        $roomType = \App\Models\RoomType::create([
            'name' => 'Standard Room',
            'base_price' => 100,
            'capacity' => 2,
            'description' => 'Standard room test',
        ]);

        // Create a room with floor_id
        $room = Room::create([
            'room_number' => '101',
            'room_type_id' => $roomType->id,
            'floor_id' => $floor->id,
            'description' => 'Test room',
            'status' => 'available',
            'is_active' => true,
        ]);

        // Verify floor_id is stored correctly
        $this->assertNotNull($room->floor_id);
        $this->assertEquals($floor->id, $room->floor_id);
    }

    public function test_room_floor_id_is_nullable()
    {
        // Create room type
        $roomType = \App\Models\RoomType::create([
            'name' => 'Standard Room',
            'base_price' => 100,
            'capacity' => 2,
            'description' => 'Standard room test',
        ]);

        // Create a room without floor_id
        $room = Room::create([
            'room_number' => '202',
            'room_type_id' => $roomType->id,
            'description' => 'Test room',
            'status' => 'available',
            'is_active' => true,
        ]);

        // Verify floor_id can be null
        $this->assertNull($room->floor_id);
    }

    public function test_room_hotelFloor_relationship()
    {
        // Create a hotel floor
        $floor = HotelFloor::create([
            'floor_number' => 2,
            'name' => 'First Floor',
            'description' => 'First floor test',
            'is_active' => true,
            'total_rooms' => 8,
        ]);

        // Create room type
        $roomType = \App\Models\RoomType::create([
            'name' => 'Deluxe Room',
            'base_price' => 150,
            'capacity' => 2,
            'description' => 'Deluxe room test',
        ]);

        // Create a room with floor_id
        $room = Room::create([
            'room_number' => '201',
            'room_type_id' => $roomType->id,
            'floor_id' => $floor->id,
            'description' => 'Test room',
            'status' => 'available',
            'is_active' => true,
        ]);

        // Verify the relationship works
        $this->assertNotNull($room->hotelFloor);
        $this->assertInstanceOf(HotelFloor::class, $room->hotelFloor);
        $this->assertEquals($floor->id, $room->hotelFloor->id);
        $this->assertEquals('First Floor', $room->hotelFloor->name);
    }

    public function test_room_floor_id_in_fillable()
    {
        $fillable = (new Room())->getFillable();
        $this->assertContains('floor_id', $fillable);
    }

    public function test_room_floor_id_cast_to_string()
    {
        $casts = (new Room())->getCasts();
        $this->assertEquals('string', $casts['floor_id']);
    }
}
