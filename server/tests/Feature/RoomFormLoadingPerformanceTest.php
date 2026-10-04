<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Hotel;
use App\Models\RoomType;
use App\Models\Floor;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

/**
 * Bug Condition Exploration Test for Room Form Loading Performance
 * 
 * **Validates: Requirements 1.1, 1.2, 1.3, 1.4, 1.5**
 * 
 * CRITICAL: This test MUST FAIL on unfixed code - failure confirms the bug exists.
 * DO NOT attempt to fix the test or the code when it fails.
 * 
 * This test encodes the expected behavior - it will validate the fix when it passes after implementation.
 * GOAL: Surface counterexamples that demonstrate the bug exists.
 */
class RoomFormLoadingPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected User $manager;
    protected Hotel $hotel;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->hotel = Hotel::factory()->create([
            'name' => 'Test Hotel',
            'currency' => 'ETB',
        ]);
        
        $this->manager = User::factory()->create([
            'role' => 'manager',
            'hotel_id' => $this->hotel->id,
        ]);
        
        Sanctum::actingAs($this->manager);
        
        // Set tenant context for testing
        config(['app.tenant_hotel_id' => $this->hotel->id]);
    }

    /**
     * Test 1: Verify N+1 Query Pattern in RoomTypeController
     * 
     * Expected on unfixed code: 6 queries for 5 room types (1 base query + 5 count queries)
     * Expected after fix: 1-2 queries total (optimized query without N+1)
     */
    public function test_it_should_not_execute_n_plus_1_queries_when_fetching_room_types()
    {
        // Arrange: Create 5 room types with varying room counts
        $roomType1 = RoomType::factory()->create([
            'hotel_id' => $this->hotel->id,
            'name' => 'Deluxe',
            'is_active' => true,
        ]);
        $roomType2 = RoomType::factory()->create([
            'hotel_id' => $this->hotel->id,
            'name' => 'Standard',
            'is_active' => true,
        ]);
        $roomType3 = RoomType::factory()->create([
            'hotel_id' => $this->hotel->id,
            'name' => 'Suite',
            'is_active' => true,
        ]);
        $roomType4 = RoomType::factory()->create([
            'hotel_id' => $this->hotel->id,
            'name' => 'Economy',
            'is_active' => true,
        ]);
        $roomType5 = RoomType::factory()->create([
            'hotel_id' => $this->hotel->id,
            'name' => 'Presidential',
            'is_active' => true,
        ]);

        // Create rooms for each type to trigger withCount('rooms')
        Floor::factory()->create(['hotel_id' => $this->hotel->id, 'floor_number' => 1]);
        Room::factory()->count(2)->create(['room_type_id' => $roomType1->id, 'hotel_id' => $this->hotel->id]);
        Room::factory()->count(3)->create(['room_type_id' => $roomType2->id, 'hotel_id' => $this->hotel->id]);
        Room::factory()->count(1)->create(['room_type_id' => $roomType3->id, 'hotel_id' => $this->hotel->id]);

        // Act: Enable query logging and execute the API request
        DB::enableQueryLog();
        
        $response = $this->getJson('/api/room-types', [
            'hotel_id' => $this->hotel->id,
            'is_active' => 1,
            'per_page' => 100,
        ]);
        
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Debug output for counterexample documentation
        $queryCount = count($queries);
        $countQueries = collect($queries)->filter(function($query) {
            return str_contains(strtolower($query['query']), 'count');
        })->count();

        // Assert: Response should be successful
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(5, $data, 'Should return all 5 room types');

        // CRITICAL ASSERTION: This will FAIL on unfixed code, confirming N+1 pattern
        // Unfixed code executes: 1 SELECT room_types + 5 SELECT COUNT(*) = 6+ queries
        // Fixed code should execute: 1-2 optimized queries without N+1 pattern
        $this->assertLessThanOrEqual(
            3,
            $queryCount,
            "Expected ≤3 queries but got {$queryCount} queries. " .
            "N+1 pattern detected: {$countQueries} COUNT queries found. " .
            "Counterexample: Fetching 5 room types triggered {$queryCount} database queries instead of 1-2 optimized queries."
        );

        // Additional assertion: No separate COUNT queries should exist after optimization
        $this->assertEquals(
            0,
            $countQueries,
            "Expected 0 separate COUNT queries but found {$countQueries}. " .
            "Counterexample: withCount('rooms') creates {$countQueries} additional COUNT queries (N+1 pattern)."
        );
    }

    /**
     * Test 2: Verify Oversized Response Payload with per_page: 100
     * 
     * Expected on unfixed code: Response includes per_page: 100 and large pagination metadata
     * Expected after fix: Response uses per_page: 50 or lower for optimized payload size
     */
    public function test_it_should_use_optimized_pagination_size_for_room_types_endpoint()
    {
        // Arrange: Create 10 room types (typical hotel size)
        RoomType::factory()->count(10)->create([
            'hotel_id' => $this->hotel->id,
            'is_active' => true,
        ]);

        // Act: Request room types with per_page: 100 (current unfixed behavior)
        $response = $this->getJson('/api/room-types', [
            'hotel_id' => $this->hotel->id,
            'is_active' => 1,
            'per_page' => 100,
        ]);

        // Assert: Response structure
        $response->assertStatus(200);
        
        $meta = $response->json('meta');
        $perPage = $meta['per_page'] ?? null;
        $responseSize = strlen(json_encode($response->json()));

        // CRITICAL ASSERTION: This documents the oversized payload issue
        // Unfixed code accepts per_page: 100, transferring unnecessary data
        // Fixed code should use per_page: 50 or lower for optimization
        $this->assertLessThanOrEqual(
            50,
            $perPage,
            "Expected per_page ≤50 for optimized payload but got {$perPage}. " .
            "Counterexample: API accepts per_page=100 resulting in {$responseSize} bytes response. " .
            "Optimization should limit per_page to 50 or lower to reduce payload size."
        );
    }

    /**
     * Test 3: Verify No Caching - Repeated API Calls Execute Identical Queries
     * 
     * This test verifies that subsequent API calls within a short time window
     * result in identical database queries (no caching behavior).
     * 
     * Expected on unfixed code: Both requests execute identical database queries
     * Expected after fix: Second request uses cached data (0 queries) within TTL window
     */
    public function test_it_should_cache_room_type_responses_to_avoid_redundant_queries()
    {
        // Arrange: Create room types
        RoomType::factory()->count(5)->create([
            'hotel_id' => $this->hotel->id,
            'is_active' => true,
        ]);

        // Act 1: First API call
        DB::enableQueryLog();
        $response1 = $this->getJson('/api/room-types', [
            'hotel_id' => $this->hotel->id,
            'is_active' => 1,
            'per_page' => 100,
        ]);
        $firstCallQueries = DB::getQueryLog();
        DB::disableQueryLog();

        $response1->assertStatus(200);
        $firstCallQueryCount = count($firstCallQueries);

        // Act 2: Second API call within 1 second (should use cache in fixed version)
        sleep(1);
        
        DB::enableQueryLog();
        $response2 = $this->getJson('/api/room-types', [
            'hotel_id' => $this->hotel->id,
            'is_active' => 1,
            'per_page' => 100,
        ]);
        $secondCallQueries = DB::getQueryLog();
        DB::disableQueryLog();

        $response2->assertStatus(200);
        $secondCallQueryCount = count($secondCallQueries);

        // Assert: Responses should be identical
        $this->assertEquals($response1->json('data'), $response2->json('data'));

        // CRITICAL ASSERTION: This will FAIL on unfixed code (no caching)
        // Unfixed code: both requests execute identical queries (e.g., 6 queries each)
        // Fixed code: second request should use cached data (0 queries within TTL)
        $this->assertEquals(
            0,
            $secondCallQueryCount,
            "Expected 0 queries on second call (cached response) but got {$secondCallQueryCount} queries. " .
            "Counterexample: First call executed {$firstCallQueryCount} queries, " .
            "second call within 1 second executed {$secondCallQueryCount} queries (no caching). " .
            "Fixed implementation should cache responses with 5-minute TTL."
        );
    }

    /**
     * Test 4: Verify Floor Endpoint Also Exhibits Similar Issues
     * 
     * Verifies that floors endpoint also suffers from performance issues
     * when fetching with per_page: 100
     */
    public function test_it_should_optimize_floors_endpoint_pagination()
    {
        // Arrange: Create floors (typical hotels have 3-8 floors)
        Floor::factory()->count(5)->create([
            'hotel_id' => $this->hotel->id,
            'is_active' => true,
        ]);

        // Act: Request floors with per_page: 100
        $response = $this->getJson('/api/floors', [
            'hotel_id' => $this->hotel->id,
            'is_active' => 1,
            'per_page' => 100,
        ]);

        // Assert
        $response->assertStatus(200);
        
        $meta = $response->json('meta');
        $perPage = $meta['per_page'] ?? null;

        // CRITICAL ASSERTION: Floors should also use optimized pagination
        $this->assertLessThanOrEqual(
            50,
            $perPage,
            "Expected per_page ≤50 for floors endpoint but got {$perPage}. " .
            "Counterexample: Floors API also accepts per_page=100 for datasets with only 3-8 records."
        );
    }

    /**
     * Test 5: Property-Based Test - Load Time Performance
     * 
     * Simulates the complete RoomForm modal loading scenario:
     * - Fetches room types, floors, and existing rooms
     * - Measures total execution time
     * 
     * Expected on unfixed code: >800ms due to sequential execution and N+1 queries
     * Expected after fix: <400ms with parallel execution and optimized queries
     */
    public function test_it_should_load_all_room_form_data_within_performance_budget()
    {
        // Arrange: Create realistic dataset
        RoomType::factory()->count(5)->create(['hotel_id' => $this->hotel->id, 'is_active' => true]);
        Floor::factory()->count(3)->create(['hotel_id' => $this->hotel->id, 'is_active' => true]);
        Room::factory()->count(10)->create(['hotel_id' => $this->hotel->id]);

        $startTime = microtime(true);

        // Act: Simulate RoomForm modal opening - execute all three API calls
        $roomTypesResponse = $this->getJson('/api/room-types', [
            'hotel_id' => $this->hotel->id,
            'is_active' => 1,
            'per_page' => 100,
        ]);

        $floorsResponse = $this->getJson('/api/floors', [
            'hotel_id' => $this->hotel->id,
            'is_active' => 1,
            'per_page' => 100,
        ]);

        $roomsResponse = $this->getJson('/api/rooms', [
            'hotel_id' => $this->hotel->id,
            'per_page' => 100,
        ]);

        $endTime = microtime(true);
        $totalLoadTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        // Assert: All responses successful
        $roomTypesResponse->assertStatus(200);
        $floorsResponse->assertStatus(200);
        $roomsResponse->assertStatus(200);

        // CRITICAL ASSERTION: Total load time should be under performance budget
        // Unfixed code: >800ms due to N+1 queries and potential sequential execution
        // Fixed code: <400ms with parallel execution and optimized queries
        $this->assertLessThan(
            400,
            $totalLoadTime,
            "Expected total load time <400ms but got {$totalLoadTime}ms. " .
            "Counterexample: Loading all RoomForm data (room types, floors, rooms) took {$totalLoadTime}ms. " .
            "Performance budget exceeded. Root cause: N+1 queries + sequential execution + no caching."
        );
    }

    /**
     * Test 6: Verify Cache Invalidation After Hotel Change
     * 
     * Ensures that switching hotels invalidates cached data properly
     */
    public function test_it_should_invalidate_cache_when_hotel_context_changes()
    {
        // Arrange: Create two hotels with different room types
        $hotel1 = $this->hotel;
        $hotel2 = Hotel::factory()->create(['name' => 'Second Hotel']);

        RoomType::factory()->count(3)->create(['hotel_id' => $hotel1->id, 'name' => 'Hotel1 Type', 'is_active' => true]);
        RoomType::factory()->count(2)->create(['hotel_id' => $hotel2->id, 'name' => 'Hotel2 Type', 'is_active' => true]);

        // Act 1: Fetch room types for hotel 1
        $response1 = $this->getJson('/api/room-types', [
            'hotel_id' => $hotel1->id,
            'is_active' => 1,
            'per_page' => 100,
        ]);

        // Act 2: Fetch room types for hotel 2 (different hotel context)
        $response2 = $this->getJson('/api/room-types', [
            'hotel_id' => $hotel2->id,
            'is_active' => 1,
            'per_page' => 100,
        ]);

        // Assert: Responses should contain different data
        $response1->assertStatus(200);
        $response2->assertStatus(200);

        $hotel1Types = $response1->json('data');
        $hotel2Types = $response2->json('data');

        $this->assertCount(3, $hotel1Types, 'Hotel 1 should have 3 room types');
        $this->assertCount(2, $hotel2Types, 'Hotel 2 should have 2 room types');

        // Verify no data leakage between hotels
        foreach ($hotel1Types as $type) {
            $this->assertStringContainsString('Hotel1', $type['name']);
        }
        foreach ($hotel2Types as $type) {
            $this->assertStringContainsString('Hotel2', $type['name']);
        }

        // CRITICAL: Cache must not return hotel1 data when requesting hotel2 data
        $this->assertNotEquals(
            $hotel1Types,
            $hotel2Types,
            "Cache invalidation failure: Hotel 2 request returned Hotel 1 data. " .
            "Counterexample: Cached responses not properly invalidated on hotel context change."
        );
    }
}
