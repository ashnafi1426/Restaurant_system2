<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Guest;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GuestOrderController extends Controller
{
    /**
     * Get room and guest info by QR token (no authentication required)
     * Token-based instead of room number for security
     */
    public function getRoom($qrToken)
    {
        try {
            Log::info('[QR ORDER] Fetching room info by token', ['qr_token' => $qrToken]);
            
            // Find room by QR token (NOT room number for security)
            $room = Room::where('qr_token', $qrToken)->first();

            if (!$room) {
                Log::warning('[QR ORDER] Token not found', ['qr_token' => $qrToken]);
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid QR code',
                    'message' => 'This QR code is not valid.',
                ], 404);
            }

            Log::info('[QR ORDER] Room found by token', [
                'room_id' => $room->id,
                'room_number' => $room->room_number,
                'room_status' => $room->status,
            ]);

            // Try to find ANY active reservation (not just checked-in)
            $activeReservation = DB::table('reservations')
                ->join('guests', 'reservations.guest_id', '=', 'guests.id')
                ->where('reservations.room_id', $room->id)
                ->whereIn('reservations.status', ['confirmed', 'checked_in'])  // Allow confirmed too
                ->orderBy('reservations.created_at', 'desc')
                ->select('reservations.id', 'guests.id as guest_id', 'guests.first_name', 'guests.last_name', 'guests.email', 'guests.phone')
                ->first();

            if (!$activeReservation) {
                Log::warning('[QR ORDER] No active reservation found', [
                    'qr_token' => $qrToken,
                    'room_number' => $room->room_number,
                    'room_id' => $room->id,
                ]);
                
                // Also log what reservations exist for debugging
                $allReservations = DB::table('reservations')
                    ->where('reservations.room_id', $room->id)
                    ->select('id', 'status', 'created_at')
                    ->get();
                Log::warning('[QR ORDER] Existing reservations for room', [
                    'room_id' => $room->id,
                    'reservations' => $allReservations,
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'No active reservation',
                    'message' => 'There is no active reservation for this room. Please check in first.',
                ], 422);
            }

            Log::info('[QR ORDER] Room & reservation validated successfully', [
                'qr_token' => $qrToken,
                'room_number' => $room->room_number,
                'guest_id' => $activeReservation->guest_id,
                'reservation_id' => $activeReservation->id,
            ]);
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $room->id,
                    'room_number' => $room->room_number,
                    'status' => $room->status,
                    'qr_token' => $qrToken,
                    'guest' => [
                        'id' => $activeReservation->guest_id,
                        'name' => "{$activeReservation->first_name} {$activeReservation->last_name}",
                        'email' => $activeReservation->email,
                        'phone' => $activeReservation->phone,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('[QR ORDER] Error validating token', [
                'qr_token' => $qrToken,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Server error',
                'message' => 'Unable to validate QR code: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Format menu item data for guest display
     * Properly constructs image URLs with validation
     */
    private function formatMenuItemForGuest($item): array
    {
        // Construct full image URL with proper validation
        $imageUrl = null;
        if ($item->image) {
            // Check if it's already a full URL (external image)
            if (filter_var($item->image, FILTER_VALIDATE_URL)) {
                $imageUrl = $item->image;
            } else {
                // Convert local path to full URL
                $imageUrl = asset('storage/' . $item->image);
            }
        }
        
        return [
            'id' => $item->id,
            'name' => $item->name,
            'description' => $item->description,
            'price' => (float) $item->price,
            'image' => $imageUrl,  // Full URL or null
        ];
    }

    /**
     * Get menu items for guest ordering
     * Public endpoint - no authentication required
     * Route: GET /api/guest/menu/{qrToken}/items
     */
    public function getMenuItems($qrToken)
    {
        try {
            Log::info('[GUEST ORDER] Fetching menu items for token', ['qr_token' => $qrToken]);

            // Optionally: Re-validate token first (extra security)
            $room = Room::where('qr_token', $qrToken)->first();
            if (!$room) {
                Log::warning('[GUEST ORDER] Token not found when fetching menu', ['qr_token' => $qrToken]);
                return response()->json([
                    'error' => 'Invalid QR code',
                ], 404);
            }

            // Get all available menu items grouped by category
            $menuItems = MenuItem::where('is_available', true)
                ->orderBy('category')
                ->orderBy('name')
                ->get();

            // Group by category with proper image URL construction
            $categorized = $menuItems->groupBy('category')
                ->map(fn($items, $category) => [
                    'category' => $category,
                    'items' => $items->map(fn($item) => $this->formatMenuItemForGuest($item))->values(),
                ])
                ->values();

            $itemsWithImages = $menuItems->whereNotNull('image')->count();
            Log::info('[GUEST ORDER] Menu items retrieved', [
                'qr_token' => $qrToken,
                'total_items' => $menuItems->count(),
                'total_categories' => $categorized->count(),
                'items_with_images' => $itemsWithImages,
            ]);

            return response()->json([
                'success' => true,
                'data' => $categorized,
            ]);
        } catch (\Exception $e) {
            Log::error('[GUEST ORDER] Error fetching menu', [
                'qr_token' => $qrToken,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'error' => 'Server error',
                'message' => 'Unable to fetch menu items.',
            ], 500);
        }
    }

    /**
     * Alternative public endpoint for menu items (no token required)
     * Route: GET /api/guest/menu/items
     */
    public function getAllMenuItems()
    {
        try {
            Log::info('[GUEST ORDER] Fetching all menu items (public)');

            // Get all available menu items grouped by category
            $menuItems = MenuItem::where('is_available', true)
                ->orderBy('category')
                ->orderBy('name')
                ->get();

            // Group by category with proper image URL construction
            $categorized = $menuItems->groupBy('category')
                ->map(fn($items, $category) => [
                    'category' => $category,
                    'items' => $items->map(fn($item) => $this->formatMenuItemForGuest($item))->values(),
                ])
                ->values();

            $itemsWithImages = $menuItems->whereNotNull('image')->count();
            Log::info('[GUEST ORDER] All menu items retrieved', [
                'total_items' => $menuItems->count(),
                'total_categories' => $categorized->count(),
                'items_with_images' => $itemsWithImages,
            ]);

            return response()->json([
                'success' => true,
                'data' => $categorized,
            ]);
        } catch (\Exception $e) {
            Log::error('[GUEST ORDER] Error fetching menu', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'error' => 'Server error',
                'message' => 'Unable to fetch menu items.',
            ], 500);
        }
    }

    /**
     * Create order from guest QR code scan (no authentication required)
     * Token-based ordering ensures security
     */
    public function createOrder(Request $request)
    {
        try {
            Log::info('[QR ORDER] Creating order from guest', [
                'qr_token' => $request->qr_token,
                'request_data' => $request->all(),
                'items_count' => count($request->get('items', [])),
            ]);

            // Validate input - use uuid for menu_item_id instead of just string
            $validated = $request->validate([
                'qr_token' => 'required|string|exists:rooms,qr_token',
                'items' => 'required|array|min:1',
                'items.*.menu_item_id' => 'required|uuid|exists:menu_items,id',
                'items.*.quantity' => 'required|integer|min:1|max:100',
                'special_requests' => 'nullable|string|max:500',
            ], [
                'items.*.menu_item_id.exists' => 'One or more menu items do not exist in our system.',
                'items.*.menu_item_id.uuid' => 'Invalid menu item format.',
                'qr_token.exists' => 'Invalid QR code token.',
            ]);

            Log::info('[QR ORDER] Validation passed', [
                'qr_token' => $validated['qr_token'],
                'items_count' => count($validated['items']),
            ]);

            // Start transaction
            return DB::transaction(function () use ($validated) {
                // Find room by QR token
                $room = Room::where('qr_token', $validated['qr_token'])->first();

                if (!$room) {
                    Log::warning('[QR ORDER] Token not found during order creation', [
                        'qr_token' => $validated['qr_token'],
                    ]);
                    return response()->json([
                        'error' => 'Invalid QR code',
                    ], 404);
                }

                // For now, create order without strict reservation check for testing
                // Get any reservation (for linking)
                $reservation = DB::table('reservations')
                    ->where('room_id', $room->id)
                    ->orderBy('created_at', 'desc')
                    ->first();

                if (!$reservation) {
                    Log::info('[QR ORDER] Creating guest and reservation for QR order', [
                        'qr_token' => $validated['qr_token'],
                        'room_id' => $room->id,
                    ]);
                    
                    // Create temporary guest
                    $guest = Guest::create([
                        'id' => Str::uuid(),
                        'first_name' => 'QR Guest',
                        'last_name' => $room->room_number,
                        'email' => 'qr-' . $room->room_number . '@hotel.local',
                        'phone' => '0000000000'
                    ]);
                    
                    // Create temporary reservation with all required fields
                    $reservationId = Str::uuid();
                    DB::table('reservations')->insert([
                        'id' => $reservationId,
                        'booking_reference' => Reservation::generateBookingReference(),
                        'room_id' => $room->id,
                        'guest_id' => $guest->id,
                        'check_in_date' => now()->format('Y-m-d'),
                        'check_out_date' => now()->addDays(1)->format('Y-m-d'),
                        'status' => 'confirmed',
                        'number_of_guests' => 1,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    
                    // Re-fetch the reservation
                    $reservation = DB::table('reservations')
                        ->where('id', $reservationId)
                        ->first();
                    
                    Log::info('[QR ORDER] Guest and reservation created', [
                        'guest_id' => $guest->id,
                        'reservation_id' => $reservation->id,
                    ]);
                }
                
                $guest_id = $reservation->guest_id;
                $reservation_id = $reservation->id;

                // Calculate total price
                $items = $validated['items'];
                $total = 0;
                $orderItems = [];

                foreach ($items as $item) {
                    $menuItem = MenuItem::findOrFail($item['menu_item_id']);
                    $lineTotal = $menuItem->price * $item['quantity'];
                    $total += $lineTotal;

                    $orderItems[] = [
                        'menu_item_id' => $menuItem->id,
                        'quantity' => $item['quantity'],
                        'item_price_at_order' => $menuItem->price,
                        'line_total' => $lineTotal,
                    ];
                }

                // Create order linked to reservation and guest
                $orderNumber = 'ORD-' . now()->format('YmdHis') . '-' . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
                $order = Order::create([
                    'order_number' => $orderNumber,
                    'room_id' => $room->id,
                    'guest_id' => $guest_id,
                    'reservation_id' => $reservation_id,
                    'total' => $total,
                    'status' => 'pending',
                    'source' => 'guest_qr', // Track that this came from guest QR scan
                    'special_requests' => $validated['special_requests'] ?? null,
                ]);

                Log::info('[QR ORDER] Order created successfully', [
                    'order_id' => $order->id,
                    'qr_token' => $validated['qr_token'],
                    'room_number' => $room->room_number,
                    'reservation_id' => $reservation->id,
                    'guest_id' => $reservation->guest_id,
                    'total' => $total,
                    'items_count' => count($orderItems),
                ]);

                // Create order items
                foreach ($orderItems as $item) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'menu_item_id' => $item['menu_item_id'],
                        'quantity' => $item['quantity'],
                        'item_price_at_order' => $item['item_price_at_order'],
                        'line_total' => $item['line_total'],
                    ]);
                }

                // Reload order with items
                $order->load('orderItems', 'room');

                return response()->json([
                    'success' => true,
                    'message' => 'Order placed successfully',
                    'data' => [
                        'id' => $order->id,
                        'room_number' => $order->room->room_number,
                        'total' => (float) $order->total,
                        'status' => $order->status,
                        'items' => $order->orderItems->map(function ($item) {
                            return [
                                'menu_item_id' => $item->menu_item_id,
                                'quantity' => $item->quantity,
                                'item_price_at_order' => (float) $item->item_price_at_order,
                                'line_total' => (float) $item->line_total,
                            ];
                        }),
                        'created_at' => $order->created_at->toIso8601String(),
                    ],
                ], 201);
            });
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('[QR ORDER] Validation error', [
                'errors' => $e->errors(),
                'request_data' => $request->all(),
            ]);
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $e->errors(),
                'debug_data' => [
                    'qr_token_provided' => $request->filled('qr_token'),
                    'items_count' => count($request->get('items', [])),
                    'first_item_keys' => count($request->get('items', [])) > 0 ? array_keys($request->get('items')[0]) : [],
                ]
            ], 422);
        } catch (\Exception $e) {
            Log::error('[QR ORDER] Error creating order', [
                'qr_token' => $request->qr_token,
                'error' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
            ]);
            return response()->json([
                'error' => 'Server error',
                'message' => 'Unable to create order.',
            ], 500);
        }
    }

    /**
     * Get order status for guest
     * Public endpoint - no authentication required
     */
    public function getOrderStatus($roomNumber)
    {
        try {
            Log::info('[GUEST ORDER] Fetching order status', ['room_number' => $roomNumber]);

            // Find room
            $room = Room::where('room_number', $roomNumber)->first();

            if (!$room) {
                return response()->json(['error' => 'Room not found'], 404);
            }

            // Get latest orders from this room
            $orders = Order::where('room_id', $room->id)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            Log::info('[GUEST ORDER] Order status retrieved', [
                'room_number' => $roomNumber,
                'orders_count' => $orders->count(),
            ]);

            return response()->json([
                'success' => true,
                'data' => $orders->map(function ($order) {
                    return [
                        'id' => $order->id,
                        'status' => $order->status,
                        'total' => (float) $order->total,
                        'created_at' => $order->created_at->toIso8601String(),
                    ];
                }),
            ]);
        } catch (\Exception $e) {
            Log::error('[GUEST ORDER] Error fetching order status', [
                'room_number' => $roomNumber,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Server error'], 500);
        }
    }
}
