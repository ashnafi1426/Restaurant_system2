<?php

namespace Database\Seeders;

use App\Models\Guest;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FixCheckInTestDataSeeder extends Seeder
{
    /**
     * This seeder:
     * 1. Resets all rooms to "available" status
     * 2. Deletes existing reservations
     * 3. Creates 3 confirmed reservations with available rooms
     */
    public function run(): void
    {
        DB::beginTransaction();

        try {
            // Step 0: Delete existing check-ins and reservations
            DB::table('check_ins')->delete();
            echo " Deleted existing check-ins\n";
            DB::table('reservations')->delete();
            echo " Deleted existing reservations\n";

            // Step 1: Reset all rooms to "available"
            DB::table('rooms')->update(['status' => 'available']);
            echo " All rooms reset to 'available' status\n";

            // Step 2: Get admin user
            $user = User::where('email', 'admin@hotel.com')->first() 
                    ?? User::first();

            if (!$user) {
                throw new \Exception('No admin user found');
            }

            // Step 3: Get available rooms
            $rooms = Room::where('is_active', true)
                        ->take(3)
                        ->get();

            if ($rooms->count() < 3) {
                throw new \Exception('Not enough active rooms available');
            }

            // Step 4: Create guests
            $guests = Guest::factory(3)->create();
            echo " Created 3 test guests\n";

            // Step 5: Create reservations with unique booking references
            $reservations = [];
            $timestamp = now()->format('Ymd') . '-' . random_int(1000, 9999);
            
            foreach ($guests as $key => $guest) {
                $reservation = Reservation::create([
                    'guest_id' => $guest->id,
                    'room_id' => $rooms[$key]->id,
                    'created_by' => $user->id,
                    'booking_reference' => 'BK-' . $timestamp . '-' . str_pad($key + 1, 2, '0', STR_PAD_LEFT),
                    'check_in_date' => now()->toDateString(),
                    'check_out_date' => now()->addDays(2)->toDateString(),
                    'number_of_guests' => 1,
                    'status' => 'confirmed',
                    'special_requests' => 'Test check-in reservation',
                ]);
                $reservations[] = $reservation;
                echo " Created reservation: {$reservation->booking_reference} for {$guest->full_name} in Room {$rooms[$key]->room_number}\n";
            }

            // Verify all rooms are still available
            $availableRooms = Room::where('status', 'available')->count();
            echo "\n Database State After Setup:\n";
            echo "  - Total available rooms: {$availableRooms}\n";
            echo "  - Total reservations: " . Reservation::count() . "\n";
            echo "  - Confirmed reservations: " . Reservation::where('status', 'confirmed')->count() . "\n";
            echo "  - Check-ins: " . DB::table('check_ins')->count() . "\n";

            DB::commit();
            echo "\n Check-in test data setup complete!\n";

        } catch (\Exception $e) {
            DB::rollBack();
            echo " Error: " . $e->getMessage() . "\n";
            throw $e;
        }
    }
}
