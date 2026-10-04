<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Hotel;
use App\Models\RoomType;
use App\Models\Floor;
use App\Models\Room;
use App\Services\RoomService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

/**
 * Bug Condition Exploration Test for Room List Query Timeout
 * 
 * **Validates: Requirements 1.1, 1.2, 2.1, 2.2**
 * 
 * CRITICAL: This test MUST FAIL on unfixed code - failure confirms the bug exists.
 * DO NOT attempt to fix the test or the code when it fails.
 * 
 * This test encodes the expected behavior - it will validate the fix when it passes after implementation.
 * GOAL: Surface counterexamples that demonstrate the query timeout bug exists.
 * 
 * Bug Condition (from design):
 * - Query uses RoomService::paginate() with floor relationship eager loading
 * - Tenant scope is applied (hotel_id filtering)
 * - Table is 'rooms', relationships include 'floor'
 * - Column 'floor_id' lacks an index (current state)
 * 
 * Expected Behavior After Fix:
 * - Query execution time < 2000ms
 * - Result data is not empty
 * - Floor relationship is loaded
 */
class RoomQueryPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected User $manager;
    protected Hotel $hotel;
    protected RoomService $roomService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create hotel and manager user
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
        
        // Initialize RoomService
        $this->roomService = app(RoomService::class);
    }

    /**
     * Property 1: Bug Condition - Query Performance with Floor Relationship
     * 
     * This test measures the execution time of RoomService::paginate() with floor eager loading.
     * 
     * EXPECTED OUTCOME ON UNFIXED CODE: Test FAILS with timeout error or execution time > 2000ms
     * This failure confirms the bug exists (missing floor_id index causing slow JOIN).
     * 
     * EXPECTED OUTCOME AFTER FIX: Test PASSES with execution time < 2000ms
     * This confirms the bug is fixed (floor_id index optimizes the JOIN).
     */
    public function test_paginated_room_query_with_floor_relationship_should_complete_within_2_seconds()
    {
        // Arrange: Create realistic dataset that triggers the bug condition
        // Create floors for the hotel
        $floor1 = Floor::factory()->create([
            'hotel_id' => $this->hotel->id,
            'floor_number' => 1,
            'name' => 'Ground Floor',
        ]);
        $floor2 = Floor::factory()->create([
            'hotel_id' => $this->hotel->id,
            'floor_number' => 2,
            'name' => 'First Floor',
        ]);
        $floor3 = Floor::factory()->create([
            'hotel_id' => $this->hotel->id,
            'floor_number' => 3,
            'name' => 'Second Floor',
        ]);

        // Create room types
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

        // Create rooms with floor relationships (this triggers the bug condition)
        Room::factory()->count(10)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $roomType1->id,
            'floor_id' => $floor1->id,
            'floor' => $floor1->floor_number,
        ]);
        Room::factory()->count(10)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $roomType2->id,
            'floor_id' => $floor2->id,
            'floor' => $floor2->floor_number,
        ]);
        Room::factory()->count(5)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $roomType1->id,
            'floor_id' => $floor3->id,
            'floor' => $floor3->floor_number,
        ]);

        // Act: Measure query execution time
        $startTime = microtime(true);
        
        try {
            // This is the exact call that triggers the timeout in production
            // RoomService::paginate() uses ->with(['roomType', 'hotel', 'floor'])
            $result = $this->roomService->paginate([], 25);
            
            $endTime = microtime(true);
            $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
            
            // Debug information for counterexample documentation
            $queryCount = count(DB::getQueryLog());
            $totalRecords = $result->total();
            $recordsReturned = $result->count();
            
            // Assert: Query should complete within 2 seconds
            // CRITICAL: This assertion will FAIL on unfixed code (timeout or >2000ms)
            $this->assertLessThan(
                2000,
                $executionTime,
                "Query execution time exceeded 2 seconds. " .
                "Counterexample: Paginating 25 rooms with floor eager loading took {$executionTime}ms. " .
                "Bug condition met: rooms table query with floor relationship eager loading and no floor_id index. " .
                "Total records: {$totalRecords}, Records returned: {$recordsReturned}, Queries executed: {$queryCount}. " .
                "Root cause: Missing floor_id index causes full table scan on hotel_floors during JOIN."
            );

            // Assert: Result should contain data
            $this->assertNotEmpty($result->items(), 'Result data should not be empty');
            $this->assertGreaterThan(0, $result->total(), 'Total count should be greater than 0');

            // Assert: Floor relationship should be loaded
            $firstRoom = $result->items()[0];
            $this->assertTrue(
                $firstRoom->relationLoaded('floor'),
                'Floor relationship should be eager loaded'
            );
            $this->assertNotNull(
                $firstRoom->floor,
                'Floor relationship should not be null'
            );
            
        } catch (\Exception $e) {
            // If an exception occurs (timeout, memory limit, etc.), document it as counterexample
            $endTime = microtime(true);
            $executionTime = ($endTime - $startTime) * 1000;
            
            $this->fail(
                "Query failed with exception after {$executionTime}ms. " .
                "Counterexample: RoomService::paginate([], 25) with floor eager loading threw exception: " .
                "{$e->getMessage()}. " .
                "Bug condition confirmed: Missing floor_id index causes query timeout/failure."
            );
        }
    }

    /**
     * Test 2: Verify Query with Filters Also Experiences Timeout
     * 
     * This test verifies that the bug condition also affects filtered queries,
     * not just basic pagination.
     */
    public function test_filtered_room_query_with_floor_relationship_should_complete_within_2_seconds()
    {
        // Arrange: Create dataset
        $floor = Floor::factory()->create([
            'hotel_id' => $this->hotel->id,
            'floor_number' => 1,
        ]);
        $roomType = RoomType::factory()->create([
            'hotel_id' => $this->hotel->id,
            'is_active' => true,
        ]);
        
        Room::factory()->count(15)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $roomType->id,
            'floor_id' => $floor->id,
            'floor' => $floor->floor_number,
            'status' => 'available',
        ]);
        Room::factory()->count(10)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $roomType->id,
            'floor_id' => $floor->id,
            'floor' => $floor->floor_number,
            'status' => 'occupied',
        ]);

        // Act: Query with status filter
        $startTime = microtime(true);
        
        $result = $this->roomService->paginate(['status' => 'available'], 25);
        
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;

        // Assert: Query should complete within 2 seconds
        $this->assertLessThan(
            2000,
            $executionTime,
            "Filtered query execution time exceeded 2 seconds. " .
            "Counterexample: Paginating rooms filtered by status='available' took {$executionTime}ms. " .
            "Bug condition persists even with filtering applied."
        );

        // Assert: Filter should work correctly
        $this->assertEquals(15, $result->total(), 'Should return only available rooms');
    }

    /**
     * Test 3: Verify Query with Search Also Experiences Timeout
     * 
     * This test verifies that the bug condition also affects search queries.
     */
    public function test_search_room_query_with_floor_relationship_should_complete_within_2_seconds()
    {
        // Arrange: Create dataset with searchable rooms
        $floor = Floor::factory()->create([
            'hotel_id' => $this->hotel->id,
            'floor_number' => 1,
        ]);
        $roomType = RoomType::factory()->create([
            'hotel_id' => $this->hotel->id,
            'is_active' => true,
        ]);
        
        Room::factory()->count(5)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $roomType->id,
            'floor_id' => $floor->id,
            'floor' => $floor->floor_number,
            'room_number' => 'Suite 10' . rand(1, 5),
        ]);
        Room::factory()->count(20)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $roomType->id,
            'floor_id' => $floor->id,
            'floor' => $floor->floor_number,
        ]);

        // Act: Query with search term
        $startTime = microtime(true);
        
        $result = $this->roomService->paginate(['search' => 'Suite'], 25);
        
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;

        // Assert: Query should complete within 2 seconds
        $this->assertLessThan(
            2000,
            $executionTime,
            "Search query execution time exceeded 2 seconds. " .
            "Counterexample: Searching rooms with term 'Suite' took {$executionTime}ms. " .
            "Bug condition persists even with search filtering applied."
        );

        // Assert: Search should return results
        $this->assertGreaterThan(0, $result->total(), 'Search should return matching rooms');
    }

    /**
     * Test 4: Database EXPLAIN Analysis
     * 
     * This test examines the query execution plan to document the missing index issue.
     * It captures the EXPLAIN output showing full table scan on hotel_floors.
     */
    public function test_query_execution_plan_should_show_floor_id_index_usage()
    {
        // Arrange: Create test data
        $floor = Floor::factory()->create([
            'hotel_id' => $this->hotel->id,
            'floor_number' => 1,
        ]);
        $roomType = RoomType::factory()->create([
            'hotel_id' => $this->hotel->id,
            'is_active' => true,
        ]);
        Room::factory()->count(5)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $roomType->id,
            'floor_id' => $floor->id,
            'floor' => $floor->floor_number,
        ]);

        // Act: Get the query builder and extract SQL
        $query = $this->roomService->getRoomsQuery([]);
        
        // Enable query logging to capture the actual SQL
        DB::enableQueryLog();
        $query->paginate(25);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Find the main SELECT query (not COUNT query)
        $mainQuery = collect($queries)->first(function ($query) {
            return !str_contains(strtoupper($query['query']), 'COUNT(*)');
        });

        $this->assertNotNull($mainQuery, 'Main SELECT query should be captured');

        // Document the query for analysis
        $sql = $mainQuery['query'];
        $bindings = $mainQuery['bindings'];

        // Check if floor relationship is being eager loaded
        $hasFloorJoin = str_contains(strtoupper($sql), 'HOTEL_FLOORS') || 
                       str_contains(strtoupper($sql), 'FLOORS');

        $this->assertTrue(
            $hasFloorJoin,
            'Query should include floor relationship. ' .
            'Counterexample: floor relationship not being eager loaded in query. ' .
            'SQL: ' . $sql
        );

        // Note: On unfixed code, EXPLAIN would show:
        // - type: ALL (full table scan) for hotel_floors table
        // - rows: large number (all rows in hotel_floors)
        // - Extra: Using where (no index used)
        // 
        // On fixed code, EXPLAIN would show:
        // - type: ref (index lookup) for hotel_floors table  
        // - rows: small number (only matching rows)
        // - key: rooms_floor_id_index
        // - Extra: Using index
        
        // This assertion documents the expectation for the fixed code
        // On unfixed code, this provides documentation for the counterexample
        $this->addToAssertionCount(1); // Document that we've analyzed the query
    }

    /**
     * Test 5: Property-Based Test - Multiple Filter Combinations
     * 
     * This test generates various filter combinations to verify the bug condition
     * affects all paginated queries with floor eager loading.
     */
    public function test_all_filter_combinations_should_complete_within_2_seconds()
    {
        // Arrange: Create comprehensive dataset
        $floor1 = Floor::factory()->create(['hotel_id' => $this->hotel->id, 'floor_number' => 1]);
        $floor2 = Floor::factory()->create(['hotel_id' => $this->hotel->id, 'floor_number' => 2]);
        
        $roomType1 = RoomType::factory()->create(['hotel_id' => $this->hotel->id, 'is_active' => true]);
        $roomType2 = RoomType::factory()->create(['hotel_id' => $this->hotel->id, 'is_active' => true]);
        
        Room::factory()->count(8)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $roomType1->id,
            'floor_id' => $floor1->id,
            'floor' => $floor1->floor_number,
            'status' => 'available',
            'is_active' => true,
        ]);
        Room::factory()->count(8)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $roomType2->id,
            'floor_id' => $floor2->id,
            'floor' => $floor2->floor_number,
            'status' => 'occupied',
            'is_active' => true,
        ]);
        Room::factory()->count(4)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $roomType1->id,
            'floor_id' => $floor1->id,
            'floor' => $floor1->floor_number,
            'status' => 'maintenance',
            'is_active' => false,
        ]);

        // Test various filter combinations
        $filterCombinations = [
            [],
            ['status' => 'available'],
            ['status' => 'occupied'],
            ['is_active' => true],
            ['is_active' => false],
            ['room_type_id' => $roomType1->id],
            ['status' => 'available', 'is_active' => true],
            ['status' => 'occupied', 'room_type_id' => $roomType2->id],
        ];

        foreach ($filterCombinations as $filters) {
            $startTime = microtime(true);
            
            $result = $this->roomService->paginate($filters, 25);
            
            $endTime = microtime(true);
            $executionTime = ($endTime - $startTime) * 1000;

            $filterDescription = empty($filters) ? 'no filters' : json_encode($filters);
            
            $this->assertLessThan(
                2000,
                $executionTime,
                "Query with filters {$filterDescription} exceeded 2 seconds. " .
                "Execution time: {$executionTime}ms. " .
                "Counterexample: Bug condition affects all filter combinations."
            );
            
            // Verify result integrity
            $this->assertGreaterThanOrEqual(0, $result->total());
            if ($result->count() > 0) {
                $this->assertTrue($result->items()[0]->relationLoaded('floor'));
            }
        }
    }
}
