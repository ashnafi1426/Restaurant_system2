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
            // Normalize token: uppercase only if it's the new 8-char format (no dashes)
            if (!str_contains($qrToken, '-')) {
                $qrToken = strtoupper($qrToken);
            }
            
            // Validate token format
            // Accept two formats:
            // 1. Exactly 8 uppercase alphanumeric (new format): ABCD1234
            // 2. Table format (legacy): table-{number}-{token}
            $isValid = preg_match('/^[A-Z0-9]{8}$/', $qrToken) || 
                       preg_match('/^table-\d+-[A-Za-z0-9]+$/', $qrToken);
            
            if (!$isValid) {
                Log::warning('Invalid QR token format', ['token' => $qrToken]);
                return [
                    'success' => false,
                    'context' => null,
                    'data' => null,
                    'message' => 'Invalid QR token format',
                ];
            }

            // Try to find a room with this QR token
            $room = Room::where('qr_token', $qrToken)
                        ->where('is_active', true)
                        ->first();

            if ($room) {
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
                    'token' => $qrToken,
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

            // Try to find a restaurant table with this QR token
            $table = RestaurantTable::where('qr_token', $qrToken)
                                    ->where('is_active', true)
                                    ->first();

            if ($table) {
                Log::info('QR Token resolved to Restaurant Table', [
                    'token' => $qrToken,
                    'table_id' => $table->id,
                    'table_number' => $table->table_number,
                ]);

                return [
                    'success' => true,
                    'context' => 'table',
                    'data' => [
                        'table_id' => $table->id,
                        'table_number' => $table->table_number,
                        'table_name' => $table->table_name,
                        'capacity' => $table->capacity,
                        'location' => $table->location,
                        'status' => $table->status,
                    ],
                    'message' => 'QR code belongs to a restaurant table',
                ];
            }

            // QR token not found in either rooms or tables
            Log::warning('QR Token not found', ['token' => $qrToken]);
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
