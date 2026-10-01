<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\RestaurantTable;
use App\Models\Room;
use App\Services\GuestOrderService;
use App\Services\MenuService;
use App\Services\QRResolutionService;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class GuestOrderController extends Controller
{
    protected MenuService $menuService;
    protected GuestOrderService $guestOrderService;

    public function __construct(MenuService $menuService, GuestOrderService $guestOrderService)
    {
        $this->menuService = $menuService;
        $this->guestOrderService = $guestOrderService;
    }

    /**
     * Resolve a room or table QR token and return status and ordering eligibility.
     */
    public function getRoom(string $qrToken): JsonResponse
    {
        try {
            $resolution = QRResolutionService::resolveQRToken($qrToken);

            if ($resolution['success']) {
                $data = $resolution['data'];

                if ($resolution['context'] === 'room') {
                    return response()->json([
                        'success' => true,
                        'data' => [
                            'id' => $data['room_id'],
                            'room_number' => $data['room_number'],
                            'status' => $data['status'] ?? 'available',
                            'qr_token' => $qrToken,
                            'context' => 'room',
                            'hotel_id' => $data['hotel_id'] ?? null,
                            'hotel_name' => $data['hotel_name'] ?? null,
                            'is_checked_in' => $data['is_checked_in'] ?? false,
                            'can_order' => $data['can_order'] ?? false,
                            'reservation_status' => $data['reservation_status'] ?? 'none',
                            'eligibility_message' => $data['eligibility_message'] ?? null,
                            'guest' => $data['guest'] ?? [
                                'id' => null,
                                'name' => 'Hotel Guest',
                                'email' => 'guest@hotel.com',
                                'phone' => '',
                            ],
                        ],
                    ]);
                }

                if ($resolution['context'] === 'table') {
                    return response()->json([
                        'success' => true,
                        'data' => [
                            'id' => $data['table_id'],
                            'room_number' => $data['table_name'] ?? ('Table ' . $data['table_number']),
                            'table_number' => $data['table_number'],
                            'status' => $data['status'] ?? 'available',
                            'qr_token' => $qrToken,
                            'context' => 'table',
                            'hotel_id' => $data['hotel_id'] ?? null,
                            'hotel_name' => $data['hotel_name'] ?? null,
                            'is_checked_in' => true,
                            'can_order' => true,
                            'reservation_status' => 'not_applicable',
                            'eligibility_message' => null,
                            'guest' => [
                                'id' => null,
                                'name' => 'Walk-in Guest',
                                'email' => 'walkin@restaurant.com',
                                'phone' => '',
                            ],
                        ],
                    ]);
                }
            }

            // Direct room lookup fallback
            $room = Room::withoutGlobalScopes()->where('qr_token', $qrToken)->first();
            if (!$room) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid QR code',
                    'message' => 'This QR code is not valid.',
                ], 404);
            }

            $currentReservation = $room->getCurrentReservation();
            $reservationStatus = $currentReservation?->status ?? 'none';
            $canOrder = ($reservationStatus === 'checked_in');

            $eligibilityMessage = match ($reservationStatus) {
                'checked_in' => 'Guest is checked in and eligible for room service.',
                'confirmed' => 'Your reservation is confirmed, but room-service ordering is only available after check-in at the front desk.',
                'checked_out' => 'This room has been checked out. Room-service ordering is no longer available.',
                'cancelled' => 'This reservation was cancelled. Room-service ordering is unavailable.',
                default => 'No active checked-in reservation found for this room. Room-service ordering is only available for checked-in guests.',
         
            };

            $guest = $currentReservation?->guest;

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $room->id,
                    'room_number' => $room->room_number,
                    'status' => $room->status,
                    'qr_token' => $qrToken,
                    'context' => 'room',
                    'hotel_id' => $room->hotel_id,
                    'hotel_name' => $room->hotel?->name,
                    'is_checked_in' => $canOrder,
                    'can_order' => $canOrder,
                    'reservation_status' => $reservationStatus,
                    'eligibility_message' => $eligibilityMessage,
                    'guest' => $guest ? [
                        'id' => $guest->id,
                        'name' => trim(($guest->first_name ?? '') . ' ' . ($guest->last_name ?? '')),
                        'email' => $guest->email,
                        'phone' => $guest->phone,
                    ] : [
                        'id' => null,
                        'name' => 'Hotel Guest',
                        'email' => 'guest@hotel.com',
                        'phone' => '',
                    ],
                ],
            ]);
        } catch (Throwable $e) {
            \Log::error('[GUEST ORDER] Error resolving QR token: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Server error',
                'message' => 'Unable to validate QR code: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get menu items for a specific scanned QR token (room or table).
     */
    public function getMenuItems(string $qrToken): JsonResponse
    {
        try {
            $resolution = QRResolutionService::resolveQRToken($qrToken);
            $hotelId = null;

            if ($resolution['success'] && !empty($resolution['data']['hotel_id'])) {
                $hotelId = $resolution['data']['hotel_id'];
            } else {
                $room = Room::withoutGlobalScopes()->where('qr_token', $qrToken)->first();
                $table = RestaurantTable::withoutGlobalScopes()->where('qr_token', $qrToken)->first();
                $hotelId = $room?->hotel_id ?? $table?->hotel_id ?? \App\Models\Hotel::value('id');
            }

            if (!$hotelId && !$resolution['success']) {
                return response()->json(['error' => 'Invalid QR code'], 404);
            }

            if ($hotelId) {
                app(TenantContext::class)->setHotelId($hotelId);
            }

            $categorized = $this->menuService->getCategorizedMenuItems($hotelId);

            return response()->json([
                'success' => true,
                'data' => $categorized,
            ]);
        } catch (Throwable $e) {
            \Log::error('[GUEST ORDER] Error fetching menu items: ' . $e->getMessage());

            return response()->json([
                'error' => 'Server error',
                'message' => 'Unable to fetch menu items.',
            ], 500);
        }
    }

    /**
     * Public/guest endpoint to get menu items with optional category filtering or flat listing.
     */
    public function getAllMenuItems(Request $request): JsonResponse
    {
        try {
            $hotelId = $request->header('X-Hotel-ID')
                ?? $request->header('x-hotel-id')
                ?? $request->query('hotel_id');

            $qrToken = $request->query('qr_token') ?? $request->header('X-QR-Token');
            if (!$hotelId && $qrToken) {
                $resolution = QRResolutionService::resolveQRToken($qrToken);
                if (!empty($resolution['data']['hotel_id'])) {
                    $hotelId = $resolution['data']['hotel_id'];
                }
            }

            if ($hotelId) {
                app(TenantContext::class)->setHotelId($hotelId);
            }

            $categorized = $this->menuService->getCategorizedMenuItems($hotelId);

            if ($request->has('per_page') || $request->query('flat')) {
                $flatItems = $categorized->flatMap(fn($cat) => $cat['items'])->values();
                if ($request->has('per_page')) {
                    $flatItems = $flatItems->take((int) $request->query('per_page'));
                }

                return response()->json([
                    'success' => true,
                    'data' => $flatItems,
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $categorized,
            ]);
        } catch (Throwable $e) {
            \Log::error('[GUEST ORDER] Error fetching all menu items: ' . $e->getMessage());

            return response()->json([
                'error' => 'Server error',
                'message' => 'Unable to fetch menu items.',
            ], 500);
        }
    }

    /**
     * Get active categories with accurate item counts for the hotel.
     */
    public function getPublicCategories(Request $request): JsonResponse
    {
        try {
            $hotelId = $request->header('X-Hotel-ID')
                ?? $request->header('x-hotel-id')
                ?? $request->query('hotel_id');

            $qrToken = $request->query('qr_token') ?? $request->header('X-QR-Token');
            if (!$hotelId && $qrToken) {
                $resolution = QRResolutionService::resolveQRToken($qrToken);
                if (!empty($resolution['data']['hotel_id'])) {
                    $hotelId = $resolution['data']['hotel_id'];
                }
            }

            if ($hotelId) {
                app(TenantContext::class)->setHotelId($hotelId);
            }

            $categories = $this->menuService->getCategoriesWithCounts($hotelId);

            return response()->json([
                'success' => true,
                'data' => $categories,
            ]);
        } catch (Throwable $e) {
            \Log::error('[GUEST ORDER] Error fetching public categories: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch categories.',
            ], 500);
        }
    }

    /**
     * Create an order from guest QR (handles both room service and walk-in table orders).
     */
    public function createOrder(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'qr_token' => 'required|string',
                'items' => 'required|array|min:1',
                'items.*.menu_item_id' => 'required|uuid|exists:menu_items,id',
                'items.*.quantity' => 'required|integer|min:1|max:100',
                'special_requests' => 'nullable|string|max:500',
                'payment_type' => 'nullable|string|in:room_charge,cash,card',
            ], [
                'items.*.menu_item_id.exists' => 'One or more menu items do not exist in our system.',
                'items.*.menu_item_id.uuid' => 'Invalid menu item format.',
            ]);

            $result = $this->guestOrderService->placeOrder($validated);

            return response()->json($result['response'], $result['status_code']);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            \Log::error('[GUEST ORDER] Error creating order: ' . $e->getMessage());

            return response()->json([
                'error' => 'Server error',
                'message' => 'Unable to create order: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Query latest order statuses for a room or table.
     */
    public function getOrderStatus(string $roomOrTableNumber): JsonResponse
    {
        try {
            $room = Room::withoutGlobalScopes()
                ->where('room_number', $roomOrTableNumber)
                ->orWhere('qr_token', $roomOrTableNumber)
                ->first();

            $table = null;
            if (!$room) {
                $table = RestaurantTable::withoutGlobalScopes()
                    ->where('table_number', $roomOrTableNumber)
                    ->orWhere('qr_token', $roomOrTableNumber)
                    ->orWhere('id', $roomOrTableNumber)
                    ->first();
            }

            if (!$room && !$table) {
                return response()->json(['error' => 'Room or table not found'], 404);
            }

            $orderQuery = Order::query()->orderBy('created_at', 'desc')->limit(5);
            if ($room) {
                $orderQuery->where('room_id', $room->id);
            } else {
                $orderQuery->where('table_id', $table->id);
            }

            $orders = $orderQuery->get();

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
        } catch (Throwable $e) {
            \Log::error('[GUEST ORDER] Error fetching order status: ' . $e->getMessage());

            return response()->json(['error' => 'Server error'], 500);
        }
    }
}
