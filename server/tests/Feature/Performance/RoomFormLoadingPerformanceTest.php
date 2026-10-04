<?php

namespace Tests\Feature\Performance;

use App\Models\Hotel;
use App\Models\HotelFloor;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Bug Condition Exploration Test: Room Form Loading Performance
 */
class RoomFormLoadingPerformanceTest extends TestCase
{
    use RefreshDatabase;

    private Hotel $hotel;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hotel = Hotel::factory()->create([
            'name' => 'Performance Test Hotel',
            'email' => 'test@hotel.com',
        ]);

        $this->user = User::factory()->create([
            'hotel_id' => $this->hotel->id,
            'email' => 'admin@test.com',
        ]);

        $this->user->assignRole('admin');
    }

    public function test_room_type_endpoint_eliminates_n_plus_1_query_pattern(): void
    {
        $roomTypes = RoomType::factory()->count(5)->create([
            'hotel_id' => $this->hotel->id,
            'is_active' => true,
        ]);

        foreach ($roomTypes as $index => $roomType) {
            Room::factory()->count($index + 1)->create([
                'hotel_id' => $this->hotel->id,
                'room_type_id' => $roomType->id,
            ]);
        }

        DB::enableQueryLog();
        
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/room-types?per_page=100&is_active=1&hotel_id=' . $this->hotel->id);

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $queryCount = count($queries);
        
        $countQueries = array_filter($queries, function ($query) {
            return stripos($query['query'], 'count') !== false && 
                   stripos($query['query'], 'rooms') !== false;
        });

        $this->assertLessThanOrEqual(3, $queryCount, 
            "Expected minimal queries (<= 3), but got {$queryCount}. N+1 query pattern detected. " .
            "Found " . count($countQueries) . " COUNT queries for rooms."
        );

        $response->assertOk();
    }

    public function test_room_form_complete_loading_performance(): void
    {
        $roomTypes = RoomType::factory()->count(5)->create([
            'hotel_id' => $this->hotel->id,
            'is_active' => true,
        ]);

        $floors = HotelFloor::factory()->count(3)->create([
            'hotel_id' => $this->hotel->id,
            'is_active' => true,
        ]);

        Room::factory()->count(10)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $roomTypes->first()->id,
            'floor_id' => $floors->first()->id,
        ]);

        $startTime = microtime(true);
        
        DB::enableQueryLog();
        
        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/room-types?per_page=50&is_active=1&hotel_id=' . $this->hotel->id);

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/floors?per_page=50&is_active=1&hotel_id=' . $this->hotel->id);

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/rooms?per_page=100&hotel_id=' . $this->hotel->id);
        
        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        
        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000;

        $queryCount = count($queries);
        
        $this->assertLessThanOrEqual(10, $queryCount,
            "Expected <=10 queries for complete form loading, but got {$queryCount}. " .
            "N+1 patterns detected."
        );

        $this->assertLessThan(400, $totalTime,
            "Expected complete loading time <400ms, but got {$totalTime}ms."
        );
    }
}