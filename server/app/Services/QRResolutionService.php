<?php

namespace App\Services;

use App\Models\Room;
use App\Models\RestaurantTable;
use Illuminate\Support\Facades\Log;

class QRResolutionService
{
    /**
     * Resolve QR token to determine context (room or table)
     * 
     * @param string $qrToken The 8-character QR token
     * @return array{
     *   success: bool,
     *   context: string|null,
     *   data: array|null,
     *   message: string|null
     * }
     */
    public static function resolveQRToken(string $qrToken): array
    {
        try {
            $rawToken = trim($qrToken);
            if (empty($rawToken)) {
                return [
                    'success' => false,
                    'context' => null,
                    'data' => null,
                    'message' => 'QR token cannot be empty',
                ];
            }

            $upperToken = strtoupper($rawToken);
            $lowerToken = strtolower($rawToken);

            // 1. Try to find a room with this QR token (or room number / id)
            $room = Room::where(function ($q) use ($rawToken, $upperToken, $lowerToken) {
                $q->where('qr_token', $rawToken)
                  ->orWhere('qr_token', $upperToken)
                  ->orWhere('qr_token', $lowerToken)
                  ->orWhere('id', $rawToken);
            })->first();

            if ($room && $room->is_active !== false) {
                // Check if there's an active check-in for this room
                $activeCheckIn = \App\Models\CheckIn::where('room_id', $room->id)
                    ->whereNull('checked_out_at')
                    ->with(['guest', 'reservation'])
                    ->first();

                $guestInfo = null;
                if ($activeCheckIn && $activeCheckIn->guest) {
                    $guestInfo = [
                        'guest_id' => $activeCheckIn->guest_id,
                        'guest_name' => $activeCheckIn->guest->first_name . ' ' . $activeCheckIn->guest->last_name,
                        'guest_email' => $activeCheckIn->guest->email,
                        'guest_phone' => $activeCheckIn->guest->phone,
                        'reservation_id' => $activeCheckIn->reservation_id,
                        'check_in_date' => $activeCheckIn->checked_in_at?->format('Y-m-d'),
                        'expected_checkout' => $activeCheckIn->expected_check_out_at?->format('Y-m-d'),
                    ];
                }

                Log::info('QR Token resolved to Room', [
                    'token' => $rawToken,
                    'room_id' => $room->id,
                    'room_number' => $room->room_number,
                    'has_guest' => $guestInfo !== null,
                    'guest_name' => $guestInfo['guest_name'] ?? null,
                ]);

                return [
                    'success' => true,
                    'context' => 'room',
                    'data' => [
                        'room_id' => $room->id,
                        'room_number' => $room->room_number,
                        'floor' => $room->floor,
                        'floor_id' => $room->floor_id,
                        'room_type' => $room->roomType ? $room->roomType->name : null,
                        'status' => $room->status,
                        'guest' => $guestInfo, // Include guest information
                    ],
                    'message' => 'QR code belongs to a hotel room',
                ];
            }

            // 2. Try to find a restaurant table with this QR token (or table number / id / slug)
            $table = RestaurantTable::where(function ($q) use ($rawToken, $upperToken, $lowerToken) {
                $q->where('qr_token', $rawToken)
                  ->orWhere('qr_token', $upperToken)
                  ->orWhere('qr_token', $lowerToken)
                  ->orWhere('table_number', $rawToken)
                  ->orWhere('table_number', $upperToken)
                  ->orWhere('id', $rawToken);

                // Handle legacy table slugs like table-1-xxx
                if (preg_match('/^table-(\d+)/i', $rawToken, $m)) {
                    $q->orWhere('table_number', $m[1]);
                }
            })->first();

            if ($table && $table->is_active !== false) {
                // Get assigned waiter for this table at current time
                $assignedWaiter = null;
                $currentShift = \App\Models\HotelShift::getCurrentShift();
                
                if ($currentShift && \Illuminate\Support\Facades\Schema::hasTable('waiter_table_assignments')) {
                    try {
                        $assignment = \App\Models\WaiterTableAssignment::getAssignedWaiter(
                            $table->id,
                            $currentShift->id,
                            today()
                        );

                        if ($assignment && $assignment->waiter) {
                            $assignedWaiter = [
                                'waiter_id' => $assignment->waiter_id,
                                'waiter_name' => $assignment->waiter->user->name ?? 'Unknown',
                                'waiter_email' => $assignment->waiter->user->email ?? null,
                                'priority' => $assignment->priority,
                                'shift' => [
                                    'id' => $currentShift->id,
                                    'name' => $currentShift->name,
                                    'start_time' => $currentShift->start_time,
                                    'end_time' => $currentShift->end_time,
                                ],
                            ];
                        }
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning('QRResolution assigned waiter lookup skipped: ' . $e->getMessage());
                    }
                }

                Log::info('QR Token resolved to Restaurant Table', [
                    'token' => $rawToken,
                    'table_id' => $table->id,
                    'table_number' => $table->table_number,
                    'has_assigned_waiter' => $assignedWaiter !== null,
                    'waiter_name' => $assignedWaiter['waiter_name'] ?? null,
                ]);

                return [
                    'success' => true,
                    'context' => 'table',
                    'data' => [
                        'table_id' => $table->id,
                        'table_number' => $table->table_number,
                        'table_name' => $table->table_name ?? ('Table ' . $table->table_number),
                        'capacity' => $table->capacity,
                        'location' => $table->location,
                        'status' => $table->status,
                        'assigned_waiter' => $assignedWaiter, // Include assigned waiter info
                    ],
                    'message' => 'QR code belongs to a restaurant table',
                ];
            }

            // QR token not found in either rooms or tables
            Log::warning('QR Token not found', ['token' => $rawToken]);
            return [
                'success' => false,
                'context' => null,
                'data' => null,
                'message' => 'QR code not found or has been deactivated',
            ];

        } catch (\Exception $e) {
            Log::error('QR Token Resolution Error', [
                'token' => $qrToken,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'context' => null,
                'data' => null,
                'message' => 'Failed to resolve QR code',
            ];
        }
    }

    /**
     * Validate order creation payload based on context
     * 
     * @param array $orderData The order data to validate
     * @param string $context Either 'room' or 'table'
     * @return array{valid: bool, errors: array}
     */
    public static function validateOrderData(array $orderData, string $context): array
    {
        $errors = [];

        if ($context === 'room') {
            // Room service orders MUST have room_id
            if (empty($orderData['room_id'])) {
                $errors[] = 'room_id is required for room service orders';
            }

            // Room service orders SHOULD have guest_id and reservation_id (but nullable for flexibility)
            // No strict validation - let business logic handle this

        } elseif ($context === 'table') {
            // Walk-in orders MUST have table_id
            if (empty($orderData['table_id'])) {
                $errors[] = 'table_id is required for walk-in orders';
            }

            // Walk-in orders should NOT have room/guest/reservation
            if (!empty($orderData['room_id']) || !empty($orderData['guest_id']) || !empty($orderData['reservation_id'])) {
                $errors[] = 'Walk-in orders should not have room, guest, or reservation associations';
            }

        } else {
            $errors[] = 'Invalid order context';
        }

        // Common validations
        if (empty($orderData['items']) || !is_array($orderData['items']) || count($orderData['items']) === 0) {
            $errors[] = 'Order must contain at least one item';
        }

        return [
            'valid' => count($errors) === 0,
            'errors' => $errors,
        ];
    }

    /**
     * Determine order type from context
     * 
     * @param string $context Either 'room' or 'table'
     * @return string The order type constant value
     */
    public static function getOrderTypeFromContext(string $context): string
    {
        return $context === 'room' ? 'room_service' : 'walk_in';
    }

    /**
     * Check if QR token belongs to an available table
     * 
     * @param string $qrToken
     * @return bool
     */
    public static function isTableAvailable(string $qrToken): bool
    {
        $table = RestaurantTable::where('qr_token', $qrToken)
                                ->where('is_active', true)
                                ->where('status', RestaurantTable::STATUS_AVAILABLE)
                                ->first();

        return $table !== null;
    }

    /**
     * Check if QR token belongs to an available room
     * 
     * @param string $qrToken
     * @return bool
     */
    public static function isRoomAvailable(string $qrToken): bool
    {
        $room = Room::where('qr_token', $qrToken)
                    ->where('is_active', true)
                    ->whereIn('status', ['occupied', 'available']) // Rooms must be occupied or available
                    ->first();

        return $room !== null;
    }
}
