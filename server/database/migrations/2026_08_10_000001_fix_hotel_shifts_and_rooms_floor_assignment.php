<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\HotelFloor;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // PART 1: Add is_active column to hotel_shifts table
        if (!Schema::hasColumn('hotel_shifts', 'is_active')) {
            Schema::table('hotel_shifts', function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->after('status');
                $table->index('is_active');
            });
            
            // Set is_active based on existing status column
            DB::statement("UPDATE hotel_shifts SET is_active = (status = 'active')");
        }

        // PART 2: Fix rooms without floor_id (assign based on floor number)
        echo "\n=== Fixing Rooms Floor Assignments ===\n";
        
        // Get all floors
        $floors = HotelFloor::all()->keyBy('floor_number');
        
        // Get all rooms without floor_id
        $roomsWithoutFloorId = DB::table('rooms')
            ->whereNull('floor_id')
            ->get();
        
        echo "Found " . $roomsWithoutFloorId->count() . " rooms without floor_id\n";
        
        foreach ($roomsWithoutFloorId as $room) {
            // Try to match by floor number
            if ($room->floor && isset($floors[$room->floor])) {
                DB::table('rooms')
                    ->where('id', $room->id)
                    ->update(['floor_id' => $floors[$room->floor]->id]);
                
                echo "   Room {$room->room_number}: floor {$room->floor} → floor_id {$floors[$room->floor]->id}\n";
            } else {
                // Assign to Ground Floor (floor 1) as default
                $groundFloor = $floors[1] ?? $floors->first();
                if ($groundFloor) {
                    DB::table('rooms')
                        ->where('id', $room->id)
                        ->update(['floor_id' => $groundFloor->id]);
                    
                    echo "   Room {$room->room_number}: No floor match, assigned to {$groundFloor->name}\n";
                }
            }
        }
        
        echo "=== Floor Assignment Complete ===\n\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove is_active column from hotel_shifts
        if (Schema::hasColumn('hotel_shifts', 'is_active')) {
            Schema::table('hotel_shifts', function (Blueprint $table) {
                $table->dropIndex(['is_active']);
                $table->dropColumn('is_active');
            });
        }
        
        // Don't reverse floor_id assignments as they're data corrections
    }
};

