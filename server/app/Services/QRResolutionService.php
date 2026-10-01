<?php

namespace App\Services;

use App\Models\Room;
use App\Models\RestaurantTable;
use Illuminate\Support\Facades\Log;

class QRResolutionService
{
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

            $room = Room::withoutGlobalScopes()->where(function ($q) use ($rawToken, $upperToken, $lowerToken) {
                $q->where('qr_token', $rawToken)
                  ->orWhere('qr_token', $upperToken)
                  ->orWhere('qr_token', $lowerToken)
                  ->orWhere('id', $rawToken);
            })->first();

            if ($room && $room->is_active !== false) {
                $currentReservation = $room->getCurrentReservation();
                $reservationStatus = $currentReservation?->status ?? 'none';
                $canOrder = ($reservationStatus === 'checked_in');

                $eligibilityMessage = match($reservationStatus) {
                    'checked_in' => 'Guest is checked in and eligible for room service.',
                    'confirmed' => 'Your reservation is confirmed, but room-service ordering is only available after check-in at the front desk.',
                    'checked_out' => 'This room has been checked out. Room-service ordering is no longer available.',
                    'cancelled' => 'This reservation was cancelled. Room-service ordering is unavailable.',
                    default => 'No active checked-in reservation found for this room. Room-service ordering is only available for checked-in guests.',
                };

                $guestInfo = null;
                if ($currentReservation && $currentReservation->guest) {
                    $guest = $currentReservation->guest;
                    $guestInfo = [
                        'guest_id' => $guest->id,
                        'guest_name' => trim(($guest->first_name ?? '') . ' ' . ($guest->last_name ?? '')),
                        'guest_email' => $guest->email,
                        'guest_phone' => $guest->phone,
                        'reservation_id' => $currentReservation->id,
                        'reservation_status' => $reservationStatus,
                        'check_in_date' => $currentReservation->check_in_date?->format('Y-m-d'),
                        'expected_checkout' => $currentReservation->check_out_date?->format('Y-m-d'),
                    ];
                }

                Log::info('QR Token resolved to Room', [
                    'token' => $rawToken,
                    'room_id' => $room->id,
                    'room_number' => $room->room_number,
                    'reservation_status' => $reservationStatus,
                    'can_order' => $canOrder,
                    'has_guest' => $guestInfo !== null,
                    'guest_name' => $guestInfo['guest_name'] ?? null,
                ]);

                return [
                    'success' => true,
                    'context' => 'room',
                    'data' => [
                        'hotel_id' => $room->hotel_id,
                        'hotel_name' => $room->hotel?->name,
                        'room_id' => $room->id,
                        'room_number' => $room->room_number,
                        'floor' => $room->floor,
                        'floor_id' => $room->floor_id,
                        'room_type' => $room->roomType ? $room->roomType->name : null,
                        'status' => $room->status,
                        'guest' => $guestInfo,
                        'reservation_id' => $currentReservation?->id,
                        'reservation_status' => $reservationStatus,
                        'is_checked_in' => $canOrder,
                        'can_order' => $canOrder,
                        'eligibility_message' => $eligibilityMessage,
                    ],
                    'message' => $canOrder 
                        ? 'QR code belongs to a hotel room' 
                        : $eligibilityMessage,
                ];
            }

            $table = RestaurantTable::withoutGlobalScopes()->where(function ($q) use ($rawToken, $upperToken, $lowerToken) {
                $q->where('qr_token', $rawToken)
                  ->orWhere('qr_token', $upperToken)
                  ->orWhere('qr_token', $lowerToken)
                  ->orWhere('table_number', $rawToken)
                  ->orWhere('table_number', $upperToken)
                  ->orWhere('id', $rawToken);

                if (preg_match('/^table-(\d+)/i', $rawToken, $m)) {
                    $q->orWhere('table_number', $m[1]);
                }
            })->first();

            if ($table && $table->is_active !== false) {
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
                        'hotel_id' => $table->hotel_id,
                        'hotel_name' => $table->hotel?->name,
                        'table_id' => $table->id,
                        'table_number' => $table->table_number,
                        'table_name' => $table->table_name ?? ('Table ' . $table->table_number),
                        'capacity' => $table->capacity,
                        'location' => $table->location,
                        'status' => $table->status,
                        'assigned_waiter' => $assignedWaiter,
                        'is_checked_in' => true,
                        'can_order' => true,
                        'reservation_status' => 'not_applicable',
                        'eligibility_message' => null,
                    ],
                    'message' => 'QR code belongs to a restaurant table',
                ];
            }

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

    public static function validateOrderData(array $orderData, string $context): array
    {
        $errors = [];

        if ($context === 'room') {
            if (empty($orderData['room_id'])) {
                $errors[] = 'room_id is required for room service orders';
            } else {
                $room = Room::withoutGlobalScopes()->find($orderData['room_id']);
                if (!$room) {
                    $errors[] = 'Room not found';
                } else {
                    $reservation = $room->getCurrentReservation();
                    if (!$reservation || $reservation->status !== 'checked_in') {
                        $currentStatus = $reservation ? $reservation->status : 'none';
                        $errors[] = match($currentStatus) {
                            'confirmed' => 'Your reservation is confirmed, but room-service ordering is only available after check-in at the front desk.',
                            'checked_out' => 'This room has been checked out. Room-service ordering is no longer available.',
                            'cancelled' => 'This reservation was cancelled. Room-service ordering is unavailable.',
                            default => 'Room service ordering is only allowed for checked-in guests.',
                        };
                    }
                }
            }

        } elseif ($context === 'table') {
            if (empty($orderData['table_id'])) {
                $errors[] = 'table_id is required for walk-in orders';
            }

            if (!empty($orderData['room_id']) || !empty($orderData['guest_id']) || !empty($orderData['reservation_id'])) {
                $errors[] = 'Walk-in orders should not have room, guest, or reservation associations';
            }

        } else {
            $errors[] = 'Invalid order context';
        }

        if (empty($orderData['items']) || !is_array($orderData['items']) || count($orderData['items']) === 0) {
            $errors[] = 'Order must contain at least one item';
        }

        return [
            'valid' => count($errors) === 0,
            'errors' => $errors,
        ];
    }

    public static function getOrderTypeFromContext(string $context): string
    {
        return $context === 'room' ? 'room_service' : 'walk_in';
    }

    public static function isTableAvailable(string $qrToken): bool
    {
        $table = RestaurantTable::where('qr_token', $qrToken)
                                ->where('is_active', true)
                                ->where('status', RestaurantTable::STATUS_AVAILABLE)
                                ->first();

        return $table !== null;
    }

    public static function isRoomAvailable(string $qrToken): bool
    {
        $room = Room::where('qr_token', $qrToken)
                    ->where('is_active', true)
                    ->whereIn('status', ['occupied', 'available'])
                    ->first();

        return $room !== null;
    }
}
