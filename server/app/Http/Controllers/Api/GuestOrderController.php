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
    public function getRoom($qrToken)
    {
        try {
            Log::info('[QR ORDER] Fetching room info by token', ['qr_token' => $qrToken]);
            $room = Room::where('qr_token', $qrToken)->first();
            if (!$room) {
                Log::warning('[QR ORDER] Token not found', ['qr_token' => $qrToken]);
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid QR code',
                    'message' => trans_msg('invalid_qr_code', default: 'This QR code is not valid.'),
                ], 404);
            }
            Log::info('[QR ORDER] Room found by token', [
                'room_id' => $room->id,
                'room_number' => $room->room_number,
                'room_status' => $room->status,
            ]);
            $activeReservation = DB::table('reservations')
                ->join('guests', 'reservations.guest_id', '=', 'guests.id')
                ->where('reservations.room_id', $room->id)
                ->whereIn('reservations.status', ['confirmed', 'checked_in'])
                ->orderBy('reservations.created_at', 'desc')
                ->select('reservations.id', 'guests.id as guest_id', 'guests.first_name', 'guests.last_name', 'guests.email', 'guests.phone')
                ->first();
            if (!$activeReservation) {
                Log::warning('[QR ORDER] No active reservation found', [
                    'qr_token' => $qrToken,
                    'room_number' => $room->room_number,
                    'room_id' => $room->id,
                ]);
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
                    'message' => trans_msg('no_active_reservation', default: 'There is no active reservation for this room. Please check in first.'),
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

    private function formatMenuItemForGuest($item): array
    {
        $imageUrl = null;
        if ($item->image) {
            if (filter_var($item->image, FILTER_VALIDATE_URL)) {
                $imageUrl = $item->image;
            } else {
                $imageUrl = asset('storage/' . $item->image);
            }
        }

        $price = (float) $item->price;
        $taxRateModel = $item->relationLoaded('taxRate') ? $item->taxRate : $item->taxRate;
        $rate = $taxRateModel ? (float) $taxRateModel->rate : 0.0;
        $taxIncluded = (bool) ($item->tax_included ?? false);

        if ($rate > 0) {
            if ($taxIncluded) {
                $basePrice = round($price / (1 + ($rate / 100)), 2);
                $taxAmount = round($price - $basePrice, 2);
                $totalPrice = $price;
            } else {
                $basePrice = $price;
                $taxAmount = round($price * ($rate / 100), 2);
                $totalPrice = round($price + $taxAmount, 2);
            }
        } else {
            $basePrice = $price;
            $taxAmount = 0.0;
            $totalPrice = $price;
        }

        return [
            'id' => $item->id,
            'name' => $item->name,
            'description' => $item->description,
            'price' => $price,
            'base_price' => $basePrice,
            'tax_amount' => $taxAmount,
            'total_price' => $totalPrice,
            'formatted_price' => number_format($price, 2),
            'formatted_total_price' => number_format($totalPrice, 2),
            'tax_rate_id' => $item->tax_rate_id,
            'tax_included' => $taxIncluded,
            'tax_rate' => $taxRateModel ? [
                'id' => $taxRateModel->id,
                'name' => $taxRateModel->name,
                'rate' => (float) $taxRateModel->rate,
                'type' => $taxRateModel->type,
            ] : null,
            'image' => $imageUrl,
            'category' => $item->category,
            'is_available' => (bool) $item->is_available,
        ];
    }

    public function getMenuItems($qrToken)
    {
        try {
            Log::info('[GUEST ORDER] Fetching menu items for token', ['qr_token' => $qrToken]);
            $room = Room::where('qr_token', $qrToken)->first();
            if (!$room) {
                Log::warning('[GUEST ORDER] Token not found when fetching menu', ['qr_token' => $qrToken]);
                return response()->json([
                    'error' => 'Invalid QR code',
                ], 404);
            }

            if ($room->hotel_id) {
                app(\App\Services\TenantContext::class)->setHotelId($room->hotel_id);
            }

            $query = MenuItem::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
                ->with('taxRate')
                ->where('is_available', true);

            if ($room->hotel_id) {
                $query->where(function ($q) use ($room) {
                    $q->where('hotel_id', $room->hotel_id)
                      ->orWhereNull('hotel_id');
                });
            }

            $menuItems = $query
                ->orderBy('category')
                ->orderBy('name')
                ->get();
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

    public function getAllMenuItems(Request $request)
    {
        try {
            $hotelId = $request->header('X-Hotel-ID') ?? $request->query('hotel_id');
            Log::info('[GUEST ORDER] Fetching all menu items (public)', ['hotel_id' => $hotelId]);

            $query = MenuItem::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
                ->with('taxRate')
                ->where('is_available', true);

            if ($hotelId) {
                $query->where(function ($q) use ($hotelId) {
                    $q->where('hotel_id', $hotelId)
                      ->orWhereNull('hotel_id');
                });
            }

            $menuItems = $query
                ->orderBy('category')
                ->orderBy('name')
                ->get();

            // If flat is requested or per_page
            if ($request->has('per_page') || $request->query('flat')) {
                $flatItems = $menuItems->map(fn($item) => $this->formatMenuItemForGuest($item))->values();
                if ($request->has('per_page')) {
                    $flatItems = $flatItems->take((int)$request->query('per_page'));
                }
                return response()->json([
                    'success' => true,
                    'data' => $flatItems,
                ]);
            }

            $categorized = $menuItems->groupBy('category')
                ->map(fn($items, $category) => [
                    'category' => $category,
                    'items' => $items->map(fn($item) => $this->formatMenuItemForGuest($item))->values(),
                ])
                ->values();

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

    public static function guessCategoryIcon($nameOrSlug)
    {
        $key = strtolower(trim(str_replace([' ', '_'], '-', $nameOrSlug ?? '')));
        return match (true) {
            str_contains($key, 'breakfast') || str_contains($key, 'morning') => 'clock',
            str_contains($key, 'soup') => 'soup',
            str_contains($key, 'appetizer') || str_contains($key, 'starter') => 'leaf',
            str_contains($key, 'salad') => 'salad',
            str_contains($key, 'main') || str_contains($key, 'entree') => 'utensils',
            str_contains($key, 'sandwich') || str_contains($key, 'burger') => 'sandwich',
            str_contains($key, 'pasta') || str_contains($key, 'noodle') => 'layers',
            str_contains($key, 'pizza') => 'pizza',
            str_contains($key, 'dessert') || str_contains($key, 'sweet') || str_contains($key, 'cake') => 'cake',
            str_contains($key, 'drink') || str_contains($key, 'beverage') || str_contains($key, 'wine') || str_contains($key, 'bar') || str_contains($key, 'coffee') => 'wine',
            default => 'utensils',
        };
    }

    public function getPublicCategories()
    {
        try {
            $dbCategories = \App\Models\Category::all();

            $itemCategories = MenuItem::select('category')->whereNotNull('category')->distinct()->pluck('category')->toArray();

            $categoryMap = [];

            foreach ($dbCategories as $c) {
                $slug = $c->slug ?: Str::slug($c->name);
                $cnt = MenuItem::where('category', $slug)
                    ->orWhere('category', $c->name)
                    ->orWhere('category_id', $c->id)
                    ->count();

                $icon = $c->icon;
                if (!$icon || $icon === 'grid' || $icon === 'menu') {
                    $icon = self::guessCategoryIcon($slug ?: $c->name);
                }

                $categoryMap[$slug] = [
                    'id' => $c->id ?: $slug,
                    'name' => $c->name,
                    'slug' => $slug,
                    'icon' => $icon,
                    'count' => $cnt,
                ];
            }

            foreach ($itemCategories as $rawCat) {
                if (!$rawCat) continue;
                $slug = Str::slug($rawCat);
                if (!isset($categoryMap[$slug])) {
                    $cnt = MenuItem::where('category', $rawCat)->count();
                    $categoryMap[$slug] = [
                        'id' => $slug,
                        'name' => ucwords(str_replace('-', ' ', $rawCat)),
                        'slug' => $slug,
                        'icon' => self::guessCategoryIcon($slug),
                        'count' => $cnt,
                    ];
                }
            }

            $result = array_values($categoryMap);

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            Log::error('[GUEST ORDER] Error fetching public categories', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch categories.',
            ], 500);
        }
    }

    public function createOrder(Request $request)
    {
        try {
            Log::info('[QR ORDER] Creating order from guest', [
                'qr_token' => $request->qr_token,
                'request_data' => $request->all(),
                'items_count' => count($request->get('items', [])),
            ]);
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
            return DB::transaction(function () use ($validated) {
                $room = Room::where('qr_token', $validated['qr_token'])->first();
                if (!$room) {
                    Log::warning('[QR ORDER] Token not found during order creation', [
                        'qr_token' => $validated['qr_token'],
                    ]);
                    return response()->json([
                        'error' => 'Invalid QR code',
                    ], 404);
                }
                $reservation = DB::table('reservations')
                    ->where('room_id', $room->id)
                    ->orderBy('created_at', 'desc')
                    ->first();
                $hotelId = $room->hotel_id 
                    ?? $request->input('hotel_id') 
                    ?? $request->header('X-Hotel-ID') 
                    ?? app(\App\Services\TenantContext::class)->getHotelId();

                if ($hotelId) {
                    app(\App\Services\TenantContext::class)->setHotelId($hotelId);
                }

                if (!$reservation) {
                    Log::info('[QR ORDER] Creating guest and reservation for QR order', [
                        'qr_token' => $validated['qr_token'],
                        'room_id' => $room->id,
                        'hotel_id' => $hotelId,
                    ]);
                    
                    $guest = Guest::create([
                        'id' => Str::uuid(),
                        'hotel_id' => $hotelId,
                        'first_name' => 'QR Guest',
                        'last_name' => $room->room_number,
                        'email' => 'qr-' . $room->room_number . '@hotel.local',
                        'phone' => '0000000000'
                    ]);
                    
                    $reservationId = Str::uuid();
                    DB::table('reservations')->insert([
                        'id' => $reservationId,
                        'hotel_id' => $hotelId,
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

                $orderNumber = 'ORD-' . now()->format('YmdHis') . '-' . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
                $order = Order::create([
                    'hotel_id' => $hotelId,
                    'order_number' => $orderNumber,
                    'room_id' => $room->id,
                    'guest_id' => $guest_id,
                    'reservation_id' => $reservation_id,
                    'order_time' => now(),
                    'total' => $total,
                    'status' => Order::STATUS_PENDING,
                    'source' => 'guest_qr',
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

                foreach ($orderItems as $item) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'menu_item_id' => $item['menu_item_id'],
                        'quantity' => $item['quantity'],
                        'item_price_at_order' => $item['item_price_at_order'],
                        'line_total' => $item['line_total'],
                    ]);
                }

                $order->load('orderItems', 'room');

                return response()->json([
                    'success' => true,
                    'message' => trans_msg('order_placed', default: 'Order placed successfully'),
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

    public function getOrderStatus($roomNumber)
    {
        try {
            Log::info('[GUEST ORDER] Fetching order status', ['room_number' => $roomNumber]);

            $room = Room::where('room_number', $roomNumber)->first();

            if (!$room) {
                return response()->json(['error' => 'Room not found'], 404);
            }

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
