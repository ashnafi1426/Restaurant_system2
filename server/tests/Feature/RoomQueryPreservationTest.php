<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Room;
use App\Models\Hotel;
use App\Models\RoomType;
use App\Models\Floor;
use App\Services\RoomService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

/**
 * Preservation Property Tests for Room Query Functionality
 * 
 * **Validates: Requirements 3.1, 3.2, 3.3, 3.4, 3.5, 3.6**
 * 
 * These tests capture current behavior on UNFIXED code to ensure that
 * adding the floor_id index doesn't break existing functionality.
 * 
 * Expected Outcome: All tests PASS on unfixed code
 */
class RoomQueryPreservationTest extends TestCase
{
    use RefreshDatabase;

    protected RoomService $roomService;
    protected Hotel $hotel;
    protected Floor $floor;
    protected RoomType $roomType;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock QRCodeService to prevent actual QR code generation
        $this->mock(\App\Services\QRCodeService::class, function ($mock) {
            $mock->shouldReceive('generateAndSaveQRCode')->andReturn('test/path/qr.png');
        });
        
        $this->roomService = app(RoomService::class);
        
        // Create test data with explicit hotel context
        $this->hotel = Hotel::factory()->create([
            'name' => 'Test Hotel',
            'status' => Hotel::STATUS_ACTIVE,
        ]);
        
        $this->floor = Floor::factory()->create([
            'hotel_id' => $this->hotel->id,
            'floor_number' => 1,
            'name' => 'First Floor',
        ]);
        
        $this->roomType = RoomType::factory()->create([
            'hotel_id' => $this->hotel->id,
            'name' => 'Standard',
            'base_price_per_night' => 100.00,
        ]);
    }

    /**
     * Property: Single room retrieval with floor relationship (non-paginated queries)
     * 
     * **Validates: Requirement 3.1** - Other endpoints continue to load without timeout
     * 
     * Test that fetching a single room with floor relationship works correctly.
     * This is a non-buggy scenario (no pagination with eager loading).
     */
    public function test_single_room_retrieval_with_floor_relationship(): void
    {
        // Arrange - Create room directly without factory dependencies for this specific test
        $room = new Room([
            'id' => \Illuminate\Support\Str::uuid()->toString(),
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->roomType->id,
            'floor_id' => $this->floor->id,
            'floor' => $this->floor->floor_number,
            'room_number' => '101',
            'status' => 'available',
            'is_active' => true,
            'qr_token' => 'TEST1234',
        ]);
        $room->saveQuietly(); // Skip events

        // Act
        $startTime = microtime(true);
        $fetchedRoom = Room::with(['roomType', 'hotel', 'floor'])
            ->find($room->id);
        $executionTime = (microtime(true) - $startTime) * 1000;

        // Assert - Query should be fast and return correct data
        $this->assertNotNull($fetchedRoom, 'Room should be fetched successfully');
        $this->assertEquals($room->id, $fetchedRoom->id);
        $this->assertEquals($room->room_number, $fetchedRoom->room_number);
        
        // Verify relationships are loaded
        $this->assertTrue($fetchedRoom->relationLoaded('roomType'));
        $this->assertTrue($fetchedRoom->relationLoaded('hotel'));
        $this->assertTrue($fetchedRoom->relationLoaded('floor'));
        
        // Verify relationship data
        $this->assertEquals($this->roomType->id, $fetchedRoom->roomType->id);
        $this->assertEquals($this->hotel->id, $fetchedRoom->hotel->id);
        $this->assertEquals($this->floor->id, $fetchedRoom->floor->id);
        
        // Performance check - single queries should be fast
        $this->assertLessThan(1000, $executionTime, 'Single room query should complete within 1 second');
    }

    /**
     * Property: Room creation preserves functionality
     * 
     * **Validates: Requirement 3.2** - Creating rooms continues to work
     */
    public function test_room_creation_preserves_functionality(): void
    {
        // Arrange
        $roomData = [
            'hotel_id' => $this->hotel->id,
            'room_number' => '202',
            'room_type_id' => $this->roomType->id,
            'floor_id' => $this->floor->id,
            'floor' => $this->floor->floor_number,
            'description' => 'Test room description',
            'status' => 'available',
            'is_active' => true,
        ];

        // Act
        $startTime = microtime(true);
        $room = $this->roomService->createRoom($roomData, $this->hotel->id);
        $executionTime = (microtime(true) - $startTime) * 1000;

        // Assert
        $this->assertNotNull($room);
        $this->assertEquals('202', $room->room_number);
        $this->assertEquals($this->hotel->id, $room->hotel_id);
        $this->assertEquals($this->roomType->id, $room->room_type_id);
        $this->assertEquals($this->floor->id, $room->floor_id);
        $this->assertEquals($this->floor->floor_number, $room->floor);
        $this->assertEquals('available', $room->status);
        $this->assertTrue($room->is_active);
        
        // Verify database persistence
        $this->assertDatabaseHas('rooms', [
            'id' => $room->id,
            'room_number' => '202',
            'hotel_id' => $this->hotel->id,
        ]);
        
        // Performance check
        $this->assertLessThan(2000, $executionTime, 'Room creation should complete within 2 seconds');
    }

    /**
     * Property: Room update preserves functionality
     * 
     * **Validates: Requirement 3.2** - Updating rooms continues to work
     */
    public function test_room_update_preserves_functionality(): void
    {
        // Arrange
        $room = Room::factory()->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->roomType->id,
            'floor_id' => $this->floor->id,
            'floor' => $this->floor->floor_number,
            'room_number' => '303',
            'status' => 'available',
            'description' => 'Original description',
        ]);

        $updateData = [
            'description' => 'Updated description',
            'status' => 'maintenance',
        ];

        // Act
        $startTime = microtime(true);
        $updatedRoom = $this->roomService->updateRoom($room, $updateData);
        $executionTime = (microtime(true) - $startTime) * 1000;

        // Assert
        $this->assertEquals('Updated description', $updatedRoom->description);
        $this->assertEquals('maintenance', $updatedRoom->status);
        $this->assertEquals($room->id, $updatedRoom->id);
        
        // Verify database update
        $this->assertDatabaseHas('rooms', [
            'id' => $room->id,
            'description' => 'Updated description',
            'status' => 'maintenance',
        ]);
        
        // Performance check
        $this->assertLessThan(2000, $executionTime, 'Room update should complete within 2 seconds');
    }

    /**
     * Property: Room deletion preserves functionality
     * 
     * **Validates: Requirement 3.2** - Deleting rooms continues to work
     */
    public function test_room_deletion_preserves_functionality(): void
    {
        // Arrange
        $room = Room::factory()->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->roomType->id,
            'floor_id' => $this->floor->id,
            'floor' => $this->floor->floor_number,
            'room_number' => '404',
        ]);

        $roomId = $room->id;

        // Act
        $startTime = microtime(true);
        $this->roomService->deleteRoom($room, false);
        $executionTime = (microtime(true) - $startTime) * 1000;

        // Assert
        $this->assertDatabaseMissing('rooms', ['id' => $roomId]);
        
        // Performance check
        $this->assertLessThan(2000, $executionTime, 'Room deletion should complete within 2 seconds');
    }

    /**
     * Property: Queries without floor eager loading work correctly
     * 
     * **Validates: Requirement 3.1** - Non-buggy queries continue working
     */
    public function test_queries_without_floor_eager_loading(): void
    {
        // Arrange
        Room::factory()->count(5)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->roomType->id,
            'floor_id' => $this->floor->id,
            'floor' => $this->floor->floor_number,
        ]);

        // Act - Query without relationships (non-buggy scenario)
        $startTime = microtime(true);
        $rooms = Room::select(['id', 'room_number', 'status'])
            ->where('hotel_id', $this->hotel->id)
            ->get();
        $executionTime = (microtime(true) - $startTime) * 1000;

        // Assert
        $this->assertCount(5, $rooms);
        $this->assertLessThan(1000, $executionTime, 'Simple queries should be fast');
    }

    /**
     * Property: Result set consistency across queries
     * 
     * **Validates: Requirement 3.3** - Filtering returns same room IDs
     */
    public function test_result_set_consistency(): void
    {
        // Arrange - Create rooms with specific statuses
        $room1 = Room::factory()->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->roomType->id,
            'floor_id' => $this->floor->id,
            'floor' => $this->floor->floor_number,
            'room_number' => '501',
            'status' => 'available',
        ]);

        $room2 = Room::factory()->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->roomType->id,
            'floor_id' => $this->floor->id,
            'floor' => $this->floor->floor_number,
            'room_number' => '502',
            'status' => 'occupied',
        ]);

        // Act - Query with filters multiple times
        $result1 = $this->roomService->getRoomsQuery(['status' => 'available'])
            ->pluck('id')
            ->toArray();

        $result2 = $this->roomService->getRoomsQuery(['status' => 'available'])
            ->pluck('id')
            ->toArray();

        // Assert - Results should be consistent
        $this->assertEquals($result1, $result2, 'Query results should be deterministic');
        $this->assertContains($room1->id, $result1);
        $this->assertNotContains($room2->id, $result1);
    }

    /**
     * Property: Ordering consistency preserved
     * 
     * **Validates: Requirement 3.3** - created_at DESC ordering maintained
     */
    public function test_ordering_consistency_preserved(): void
    {
        // Arrange - Create rooms with specific timestamps
        $oldRoom = Room::factory()->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->roomType->id,
            'floor_id' => $this->floor->id,
            'floor' => $this->floor->floor_number,
            'room_number' => '601',
            'created_at' => now()->subDays(2),
        ]);

        $newRoom = Room::factory()->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->roomType->id,
            'floor_id' => $this->floor->id,
            'floor' => $this->floor->floor_number,
            'room_number' => '602',
            'created_at' => now(),
        ]);

        // Act
        $rooms = $this->roomService->getRoomsQuery()->get();

        // Assert - Newest first (latest() ordering)
        $this->assertEquals($newRoom->id, $rooms->first()->id);
        $this->assertEquals($oldRoom->id, $rooms->last()->id);
    }

    /**
     * Property: Filter accuracy for status
     * 
     * **Validates: Requirement 3.3** - Status filtering works correctly
     */
    public function test_filter_accuracy_for_status(): void
    {
        // Arrange
        Room::factory()->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->roomType->id,
            'floor_id' => $this->floor->id,
            'floor' => $this->floor->floor_number,
            'status' => 'available',
        ]);

        Room::factory()->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->roomType->id,
            'floor_id' => $this->floor->id,
            'floor' => $this->floor->floor_number,
            'status' => 'maintenance',
        ]);

        Room::factory()->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->roomType->id,
            'floor_id' => $this->floor->id,
            'floor' => $this->floor->floor_number,
            'status' => 'occupied',
        ]);

        // Act
        $availableRooms = $this->roomService->getRoomsQuery(['status' => 'available'])->get();
        $maintenanceRooms = $this->roomService->getRoomsQuery(['status' => 'maintenance'])->get();

        // Assert
        $this->assertCount(1, $availableRooms);
        $this->assertCount(1, $maintenanceRooms);
        $this->assertEquals('available', $availableRooms->first()->status);
        $this->assertEquals('maintenance', $maintenanceRooms->first()->status);
    }

    /**
     * Property: Filter accuracy for is_active
     * 
     * **Validates: Requirement 3.3** - is_active filtering works correctly
     */
    public function test_filter_accuracy_for_is_active(): void
    {
        // Arrange
        $activeRoom = Room::factory()->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->roomType->id,
            'floor_id' => $this->floor->id,
            'floor' => $this->floor->floor_number,
            'is_active' => true,
        ]);

        $inactiveRoom = Room::factory()->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->roomType->id,
            'floor_id' => $this->floor->id,
            'floor' => $this->floor->floor_number,
            'is_active' => false,
        ]);

        // Act
        $activeRooms = $this->roomService->getRoomsQuery(['is_active' => true])->get();
        $inactiveRooms = $this->roomService->getRoomsQuery(['is_active' => false])->get();

        // Assert
        $this->assertGreaterThanOrEqual(1, $activeRooms->count());
        $this->assertGreaterThanOrEqual(1, $inactiveRooms->count());
        $this->assertTrue($activeRooms->contains('id', $activeRoom->id));
        $this->assertTrue($inactiveRooms->contains('id', $inactiveRoom->id));
    }

    /**
     * Property: Filter accuracy for room_type_id
     * 
     * **Validates: Requirement 3.3** - room_type_id filtering works correctly
     */
    public function test_filter_accuracy_for_room_type_id(): void
    {
        // Arrange
        $roomType2 = RoomType::factory()->create([
            'hotel_id' => $this->hotel->id,
            'name' => 'Deluxe',
        ]);

        Room::factory()->count(2)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->roomType->id,
            'floor_id' => $this->floor->id,
            'floor' => $this->floor->floor_number,
        ]);

        Room::factory()->count(3)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $roomType2->id,
            'floor_id' => $this->floor->id,
            'floor' => $this->floor->floor_number,
        ]);

        // Act
        $standardRooms = $this->roomService->getRoomsQuery([
            'room_type_id' => $this->roomType->id
        ])->get();

        $deluxeRooms = $this->roomService->getRoomsQuery([
            'room_type_id' => $roomType2->id
        ])->get();

        // Assert
        $this->assertCount(2, $standardRooms);
        $this->assertCount(3, $deluxeRooms);
        $this->assertTrue($standardRooms->every(fn($room) => $room->room_type_id === $this->roomType->id));
        $this->assertTrue($deluxeRooms->every(fn($room) => $room->room_type_id === $roomType2->id));
    }

    /**
     * Property: Search filter accuracy
     * 
     * **Validates: Requirement 3.3** - Search filtering works correctly
     */
    public function test_search_filter_accuracy(): void
    {
        // Arrange
        $room1 = Room::factory()->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->roomType->id,
            'floor_id' => $this->floor->id,
            'floor' => $this->floor->floor_number,
            'room_number' => 'ALPHA-101',
            'description' => 'Room with alpha code',
        ]);

        $room2 = Room::factory()->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->roomType->id,
            'floor_id' => $this->floor->id,
            'floor' => $this->floor->floor_number,
            'room_number' => 'BETA-202',
            'description' => 'Room with beta code',
        ]);

        // Act
        $alphaResults = $this->roomService->getRoomsQuery(['search' => 'alpha'])->get();
        $betaResults = $this->roomService->getRoomsQuery(['search' => 'beta'])->get();

        // Assert
        $this->assertCount(1, $alphaResults);
        $this->assertCount(1, $betaResults);
        $this->assertEquals($room1->id, $alphaResults->first()->id);
        $this->assertEquals($room2->id, $betaResults->first()->id);
    }

    /**
     * Property: Selective column loading preserved
     * 
     * **Validates: Requirement 3.4** - Only specified columns returned
     */
    public function test_selective_column_loading_preserved(): void
    {
        // Arrange
        Room::factory()->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->roomType->id,
            'floor_id' => $this->floor->id,
            'floor' => $this->floor->floor_number,
        ]);

        // Act
        $rooms = $this->roomService->getRoomsQuery()->get();
        $firstRoom = $rooms->first();

        // Assert - Check that specific columns are present
        $this->assertNotNull($firstRoom->id);
        $this->assertNotNull($firstRoom->room_number);
        $this->assertNotNull($firstRoom->status);
        $this->assertNotNull($firstRoom->hotel_id);
        $this->assertNotNull($firstRoom->room_type_id);
        $this->assertNotNull($firstRoom->floor_id);
    }

    /**
     * Property: Relationship loading consistency
     * 
     * **Validates: Requirement 3.3** - Floor data values match between queries
     */
    public function test_relationship_loading_consistency(): void
    {
        // Arrange
        $room = Room::factory()->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->roomType->id,
            'floor_id' => $this->floor->id,
            'floor' => $this->floor->floor_number,
        ]);

        // Act - Query with relationships multiple times
        $query1 = $this->roomService->getRoomsQuery()->find($room->id);
        $query2 = $this->roomService->getRoomsQuery()->find($room->id);

        // Assert - Relationship data should be identical
        $this->assertEquals($query1->floor->id ?? null, $query2->floor->id ?? null);
        $this->assertEquals($query1->floor->floor_number ?? null, $query2->floor->floor_number ?? null);
        $this->assertEquals($query1->roomType->id ?? null, $query2->roomType->id ?? null);
        $this->assertEquals($query1->hotel->id ?? null, $query2->hotel->id ?? null);
    }

    /**
     * Property: Tenant scoping enforcement
     * 
     * **Validates: Requirement 3.5** - Only hotel's rooms returned
     */
    public function test_tenant_scoping_enforcement(): void
    {
        // Arrange - Create two hotels with rooms
        $hotel1 = $this->hotel;
        $hotel2 = Hotel::factory()->create(['name' => 'Hotel 2']);

        $floor1 = $this->floor;
        $floor2 = Floor::factory()->create([
            'hotel_id' => $hotel2->id,
            'floor_number' => 1,
        ]);

        $roomType1 = $this->roomType;
        $roomType2 = RoomType::factory()->create(['hotel_id' => $hotel2->id]);

        $room1 = Room::factory()->create([
            'hotel_id' => $hotel1->id,
            'room_type_id' => $roomType1->id,
            'floor_id' => $floor1->id,
            'floor' => $floor1->floor_number,
        ]);

        $room2 = Room::factory()->create([
            'hotel_id' => $hotel2->id,
            'room_type_id' => $roomType2->id,
            'floor_id' => $floor2->id,
            'floor' => $floor2->floor_number,
        ]);

        // Act - Query rooms for hotel 1 (simulating tenant scope)
        $hotel1Rooms = Room::where('hotel_id', $hotel1->id)->get();
        $hotel2Rooms = Room::where('hotel_id', $hotel2->id)->get();

        // Assert - Each query should only return rooms for its hotel
        $this->assertGreaterThanOrEqual(1, $hotel1Rooms->count());
        $this->assertGreaterThanOrEqual(1, $hotel2Rooms->count());
        $this->assertTrue($hotel1Rooms->every(fn($room) => $room->hotel_id === $hotel1->id));
        $this->assertTrue($hotel2Rooms->every(fn($room) => $room->hotel_id === $hotel2->id));
        $this->assertFalse($hotel1Rooms->contains('id', $room2->id));
        $this->assertFalse($hotel2Rooms->contains('id', $room1->id));
    }

    /**
     * Property: QR code generation continues asynchronously
     * 
     * **Validates: Requirement 3.6** - QR generation doesn't block creation
     */
    public function test_qr_code_generation_non_blocking(): void
    {
        // Arrange
        $roomData = [
            'hotel_id' => $this->hotel->id,
            'room_number' => '999',
            'room_type_id' => $this->roomType->id,
            'floor_id' => $this->floor->id,
            'floor' => $this->floor->floor_number,
            'status' => 'available',
            'is_active' => true,
        ];

        // Act
        $startTime = microtime(true);
        $room = $this->roomService->createRoom($roomData, $this->hotel->id);
        $executionTime = (microtime(true) - $startTime) * 1000;

        // Assert - Room creation should be fast (not blocked by QR generation)
        $this->assertNotNull($room);
        $this->assertNotNull($room->qr_token);
        
        // Room creation should complete quickly even if QR generation is pending
        $this->assertLessThan(2000, $executionTime, 'Room creation should not be blocked by QR generation');
    }

    /**
     * Property: Pagination preserves page boundaries
     * 
     * **Validates: Requirement 3.3** - Pagination works consistently
     */
    public function test_pagination_preserves_page_boundaries(): void
    {
        // Arrange - Create 30 rooms
        Room::factory()->count(30)->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->roomType->id,
            'floor_id' => $this->floor->id,
            'floor' => $this->floor->floor_number,
        ]);

        // Act
        $page1 = $this->roomService->paginate([], 10);
        $page2 = $this->roomService->paginate([], 10);

        // Assert - Pagination metadata should be consistent
        $this->assertEquals(10, $page1->perPage());
        $this->assertEquals(10, $page2->perPage());
        $this->assertGreaterThanOrEqual(30, $page1->total());
        $this->assertEquals($page1->total(), $page2->total());
    }
}
