<?php

namespace Database\Seeders;

use App\Models\Guest;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReservationSeeder extends Seeder
{
    public function run(): void
    {
        // Create test guests
        $guests = Guest::factory(3)->create();

        // Get ONLY available rooms (not occupied, reserved, or maintenance)
        $availableRooms = Room::where('status', 'available')->get();

        // Get a user (admin)
        $user = User::first();

        // Create reservations ONLY for available rooms
        foreach ($guests as $key => $guest) {
            if ($availableRooms->count() > 0) {
                $room = $availableRooms[$key % $availableRooms->count()];
                
                Reservation::create([
                    'guest_id' => $guest->id,
                    'room_id' => $room->id,
                    'created_by' => $user?->id,
                    'booking_reference' => 'BK-' . now()->format('Ymd') . '-' . str_pad($key + 1, 4, '0', STR_PAD_LEFT),
                    'check_in_date' => now()->addDays($key),
                    'check_out_date' => now()->addDays($key + 2),
                    'number_of_guests' => 1,
                    'status' => 'confirmed',
                    'special_requests' => 'Test reservation',
                ]);
            }
        }
    }
}
