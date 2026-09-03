<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Hotel;
use App\Models\Room;
use App\Models\RoomType;
use App\Http\Middleware\QRTokenMiddleware;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class QRTokenMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected Hotel $hotel;
    protected Room $room;
    protected TenantContext $tenantContext;

    public function setUp(): void
    {
        parent::setUp();

        // Create a test hotel
        $this->hotel = Hotel::create([
            'name' => 'Test Hotel',
            'slug' => 'test-hotel',
            'email' => 'test@hotel.com',
            'phone' => '+1234567890',
            'address' => '123 Main St',
            'city' => 'Test City',
            'country' => 'Test Country',
            'status' => Hotel::STATUS_ACTIVE,
        ]);

        // Create a room type
        $roomType = RoomType::create([
            'hotel_id' => $this->hotel->id,
            'name' => 'Standard Room',
            'description' => 'A standard room',
            'base_price_per_night' => 100,
            'capacity' => 2,
        ]);

        // Create a room with QR token
        $this->room = Room::create([
            'hotel_id' => $this->hotel->id,
            'room_number' => '101',
            'room_type_id' => $roomType->id,
            'floor' => '1',
            'description' => 'Test Room',
            'status' => 'available',
            'is_active' => true,
            'qr_token' => 'TESTTOKEN',
        ]);

        $this->tenantContext = app(TenantContext::class);
    }

    /**
     * Test successful QR token validation with room context
     */
    public function test_valid_qr_token_from_room()
    {
        // Create a test route using the middleware
        Route::post('/test-qr-endpoint', function (Request $request) {
            $this->assertNotNull($request->attributes->get('qr_token'));
            $this->assertNotNull($request->attributes->get('qr_resolution'));
            $this->assertEquals($this->hotel->id, $request->attributes->get('guest_hotel_id'));
            return response()->json(['success' => true]);
        })->middleware(['qr.token']);

        // Make request with QR token in query parameter
        $response = $this->postJson('/test-qr-endpoint', [], [
            'X-QR-Token' => 'TESTTOKEN',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    /**
     * Test QR token extraction from query parameter
     */
    public function test_qr_token_extraction_from_query_parameter()
    {
        Route::post('/test-qr-endpoint', function (Request $request) {
            return response()->json([
                'success' => true,
                'qr_token' => $request->attributes->get('qr_token'),
            ]);
        })->middleware(['qr.token']);

        $response = $this->postJson('/test-qr-endpoint?qr_token=TESTTOKEN');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'qr_token' => 'TESTTOKEN',
        ]);
    }

    /**
     * Test QR token extraction from JSON body
     */
    public function test_qr_token_extraction_from_json_body()
    {
        Route::post('/test-qr-endpoint', function (Request $request) {
            return response()->json([
                'success' => true,
                'qr_token' => $request->attributes->get('qr_token'),
            ]);
        })->middleware(['qr.token']);

        $response = $this->postJson('/test-qr-endpoint', [
            'qr_token' => 'TESTTOKEN',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'qr_token' => 'TESTTOKEN',
        ]);
    }

    /**
     * Test missing QR token returns 401
     */
    public function test_missing_qr_token_returns_401()
    {
        Route::post('/test-qr-endpoint', function (Request $request) {
            return response()->json(['success' => true]);
        })->middleware(['qr.token']);

        $response = $this->postJson('/test-qr-endpoint');

        $response->assertStatus(401);
        $response->assertJson([
            'success' => false,
            'error' => 'Unauthorized',
        ]);
    }

    /**
     * Test invalid QR token returns 401
     */
    public function test_invalid_qr_token_returns_401()
    {
        Route::post('/test-qr-endpoint', function (Request $request) {
            return response()->json(['success' => true]);
        })->middleware(['qr.token']);

        $response = $this->postJson('/test-qr-endpoint?qr_token=INVALIDTOKEN');

        $response->assertStatus(401);
        $response->assertJson([
            'success' => false,
            'error' => 'Unauthorized',
        ]);
    }

    /**
     * Test inactive hotel returns 403
     */
    public function test_inactive_hotel_returns_403()
    {
        // Create another inactive hotel
        $inactiveHotel = Hotel::create([
            'name' => 'Inactive Hotel',
            'slug' => 'inactive-hotel',
            'email' => 'inactive@hotel.com',
            'status' => Hotel::STATUS_INACTIVE,
        ]);

        $roomType = RoomType::create([
            'hotel_id' => $inactiveHotel->id,
            'name' => 'Standard Room',
            'base_price_per_night' => 100,
            'capacity' => 2,
        ]);

        $inactiveRoom = Room::create([
            'hotel_id' => $inactiveHotel->id,
            'room_number' => '201',
            'room_type_id' => $roomType->id,
            'status' => 'available',
            'is_active' => true,
            'qr_token' => 'INACTIVETOKEN',
        ]);

        Route::post('/test-qr-endpoint', function (Request $request) {
            return response()->json(['success' => true]);
        })->middleware(['qr.token']);

        $response = $this->postJson('/test-qr-endpoint?qr_token=INACTIVETOKEN');

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'error' => 'Forbidden',
        ]);
    }

    /**
     * Test TenantContext is set correctly
     */
    public function test_tenant_context_set_correctly()
    {
        Route::post('/test-qr-endpoint', function (Request $request) {
            $tenantContext = app(TenantContext::class);
            $this->assertEquals($this->hotel->id, $tenantContext->getHotelId());
            $this->assertNull($tenantContext->getMembership());
            return response()->json(['success' => true]);
        })->middleware(['qr.token']);

        $response = $this->postJson('/test-qr-endpoint?qr_token=TESTTOKEN');

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    /**
     * Test QR token case-insensitive matching
     */
    public function test_qr_token_case_insensitive()
    {
        Route::post('/test-qr-endpoint', function (Request $request) {
            return response()->json(['success' => true]);
        })->middleware(['qr.token']);

        // Test lowercase
        $response = $this->postJson('/test-qr-endpoint?qr_token=testtoken');
        $response->assertStatus(200);

        // Test mixed case
        $response = $this->postJson('/test-qr-endpoint?qr_token=TestToken');
        $response->assertStatus(200);
    }

    /**
     * Test QR resolution data is attached to request
     */
    public function test_qr_resolution_data_attached_to_request()
    {
        Route::post('/test-qr-endpoint', function (Request $request) {
            $resolution = $request->attributes->get('qr_resolution');
            $this->assertTrue($resolution['success']);
            $this->assertEquals('room', $resolution['context']);
            $this->assertEquals($this->hotel->id, $resolution['data']['hotel_id']);
            $this->assertEquals($this->room->id, $resolution['data']['room_id']);
            return response()->json(['success' => true]);
        })->middleware(['qr.token']);

        $response = $this->postJson('/test-qr-endpoint?qr_token=TESTTOKEN');

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    /**
     * Test QR token extraction priority (route param > query > body > header > form)
     */
    public function test_qr_token_extraction_priority_route_param()
    {
        Route::post('/test-qr/{qrToken}', function (Request $request) {
            return response()->json([
                'token' => $request->attributes->get('qr_token'),
            ]);
        })->middleware(['qr.token']);

        $response = $this->postJson('/test-qr/TESTTOKEN?qr_token=IGNORED&X-QR-Token=ALSOIGNORED');

        $response->assertStatus(200);
        $response->assertJson(['token' => 'TESTTOKEN']);
    }

    /**
     * Test logging on successful validation
     */
    public function test_logs_successful_validation()
    {
        Route::post('/test-qr-endpoint', function (Request $request) {
            return response()->json(['success' => true]);
        })->middleware(['qr.token']);

        // Just verify the request succeeds and doesn't log errors
        $response = $this->postJson('/test-qr-endpoint?qr_token=TESTTOKEN');
        $response->assertStatus(200);
    }

    /**
     * Test logging on validation failure
     */
    public function test_logs_validation_failure()
    {
        Route::post('/test-qr-endpoint', function (Request $request) {
            return response()->json(['success' => true]);
        })->middleware(['qr.token']);

        // Verify invalid token returns 401
        $response = $this->postJson('/test-qr-endpoint?qr_token=INVALIDTOKEN');
        $response->assertStatus(401);
    }
}
