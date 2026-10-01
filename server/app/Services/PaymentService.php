<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Order;
use App\Models\Guest;
use App\Mail\ReservationConfirmed;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PaymentService
{
    public function createReservationPayment(array $data): Payment
    {
        try {
            $metadata = $data['metadata'] ?? [];
            $metadata['type'] = 'reservation';
            $metadata['created_at'] = now()->toIso8601String();
            
            $amount = (int)($data['amount'] * 100) / 100;
            
            Log::info('Creating Payment Record', [
                'amount' => $amount,
                'amount_original' => $data['amount'],
                'guest_id' => $data['guest_id'] ?? null,
            ]);
            
            $hotelId = $data['hotel_id'] ?? ($metadata['hotel_id'] ?? null);

            $txRef = (new ChapaService())->generateTransactionReference();

            $payment = Payment::create([
                'hotel_id'              => $hotelId,
                'tx_ref'                => $txRef,
                'transaction_reference' => $txRef,
                'amount'                => $amount,
                'currency'              => 'ETB',
                'first_name'            => $data['first_name'],
                'last_name'             => $data['last_name'],
                'email'                 => $data['email'],
                'phone'                 => $data['phone'],
                'payment_provider'      => Payment::PROVIDER_CHAPA,
                'status'                => Payment::STATUS_PENDING,
                'guest_id'              => $data['guest_id'] ?? null,
                'metadata'              => $metadata,
            ]);

            Log::info('Reservation Payment Created', [
                'payment_id' => $payment->id,
                'amount'     => $payment->amount,
                'guest_id'   => $data['guest_id'] ?? null,
            ]);

            return $payment;

        } catch (\Exception $e) {
            Log::error('Create Reservation Payment Failed', [
                'message' => $e->getMessage(),
                'data'    => $data,
            ]);

            throw $e;
        }
    }

    public function createOrderPayment(array $data): Payment
    {
        try {
            $metadata = $data['metadata'] ?? [];
            
            $metadata['type'] = 'order';
            $metadata['room_id'] = $data['room_id'] ?? ($metadata['room_id'] ?? null);
            $metadata['created_at'] = now()->toIso8601String();

            Log::info('[PAYMENT] Creating order payment with metadata', [
                'has_items' => isset($metadata['items']),
                'items_count' => isset($metadata['items']) ? count($metadata['items']) : 0,
                'has_calculation' => isset($metadata['calculation']),
                'metadata_keys' => array_keys($metadata),
            ]);

            $hotelId = $data['hotel_id'] 
                ?? ($metadata['hotel_id'] ?? null)
                ?? (!empty($data['room_id']) ? \App\Models\Room::withoutGlobalScopes()->where('id', $data['room_id'])->value('hotel_id') : null)
                ?? app(\App\Services\TenantContext::class)->getHotelId();

            if ($hotelId) {
                $metadata['hotel_id'] = $hotelId;
            }

            $payment = Payment::create([
                'hotel_id'         => $hotelId,
                'tx_ref'           => (new ChapaService())->generateTransactionReference(),
                'amount'           => $data['amount'],
                'currency'         => 'ETB',
                'first_name'       => $data['first_name'],
                'last_name'        => $data['last_name'],
                'email'            => $data['email'],
                'phone'            => $data['phone'],
                'payment_provider' => Payment::PROVIDER_CHAPA,
                'status'           => Payment::STATUS_PENDING,
                'guest_id'         => $data['guest_id'],
                'metadata'         => $metadata,
            ]);

            $savedMetadata = $payment->fresh()->metadata;
            Log::info(' [PAYMENT] Order Payment Created', [
                'payment_id' => $payment->id,
                'amount'     => $payment->amount,
                'guest_id'   => $data['guest_id'],
                'saved_metadata_has_items' => isset($savedMetadata['items']),
                'saved_items_count' => isset($savedMetadata['items']) ? count($savedMetadata['items']) : 0,
            ]);

            return $payment;

        } catch (\Exception $e) {
            Log::error('Create Order Payment Failed', [
                'message' => $e->getMessage(),
                'data'    => $data,
            ]);

            throw $e;
        }
    }

    public function handleReservationPaymentSuccess(Payment $payment, array $reservationData): array
    {
        try {
            // Check if reservation already created and linked
            if (!empty($payment->reservation_id)) {
                $existing = Reservation::withoutGlobalScopes()->find($payment->reservation_id);
                if ($existing) {
                    Log::info('[PaymentService] Reservation already exists for payment, returning existing record', [
                        'payment_id'     => $payment->id,
                        'reservation_id' => $existing->id,
                        'booking_ref'    => $existing->booking_reference,
                    ]);
                    $existing->loadMissing(['room.roomType', 'guest']);
                    return [
                        'success'     => true,
                        'reservation' => $existing,
                        'message'     => 'Reservation already created',
                    ];
                }
            }

            // Also check if matching reservation was created in the last 5 minutes for this hotel, guest, room, and dates
            $hotelId = $payment->hotel_id ?? ($payment->metadata['hotel_id'] ?? null) ?? ($reservationData['hotel_id'] ?? null);
            $duplicateCheck = Reservation::withoutGlobalScopes()
                ->where('hotel_id', $hotelId)
                ->where('guest_id', $reservationData['guest_id'])
                ->where('room_id', $reservationData['room_id'])
                ->where('check_in_date', $reservationData['check_in_date'])
                ->where('check_out_date', $reservationData['check_out_date'])
                ->where('created_at', '>=', now()->subMinutes(5))
                ->first();

            if ($duplicateCheck) {
                Log::info('[PaymentService] Recent matching reservation found, linking to payment to avoid duplicate', [
                    'payment_id'     => $payment->id,
                    'reservation_id' => $duplicateCheck->id,
                    'booking_ref'    => $duplicateCheck->booking_reference,
                ]);
                $payment->update(['reservation_id' => $duplicateCheck->id]);
                $duplicateCheck->loadMissing(['room.roomType', 'guest']);
                return [
                    'success'     => true,
                    'reservation' => $duplicateCheck,
                    'message'     => 'Reservation already created',
                ];
            }

            $reservation = DB::transaction(function () use ($payment, $reservationData, $hotelId) {
                $reservation = Reservation::create([
                    'hotel_id'          => $hotelId,
                    'booking_reference' => Reservation::generateBookingReference(),
                    'guest_id'          => $reservationData['guest_id'],
                    'room_id'           => $reservationData['room_id'],
                    'check_in_date'     => $reservationData['check_in_date'],
                    'check_out_date'    => $reservationData['check_out_date'],
                    'number_of_guests'  => $reservationData['number_of_guests'],
                    'status'            => 'confirmed',
                    'special_requests'  => $reservationData['special_requests'] ?? null,
                    'total_amount'      => $payment->amount,
                    'created_by'        => auth()->id() ?? null,
                ]);

                $payment->update(['reservation_id' => $reservation->id]);

                $reservation->load(['room.roomType', 'guest']);
                $guestEmail = $reservation->guest?->email;
                if ($guestEmail) {
                    try {
                        Mail::to($guestEmail)->send(new ReservationConfirmed($reservation));
                        Log::info('Automatic confirmation email sent to guest in PaymentService', [
                            'reservation_id' => $reservation->id,
                            'guest_email'    => $guestEmail,
                        ]);
                    } catch (\Exception $mailEx) {
                        Log::error('Failed to send confirmation email in PaymentService: ' . $mailEx->getMessage());
                    }
                }

                Log::info('Reservation Created and Confirmed After Payment', [
                    'payment_id'     => $payment->id,
                    'reservation_id' => $reservation->id,
                    'guest_id'       => $reservationData['guest_id'],
                    'total_amount'   => $payment->amount,
                    'status'         => $reservation->status,
                ]);

                return $reservation;
            });

            return [
                'success'     => true,
                'reservation' => $reservation,
                'message'     => 'Reservation created successfully',
            ];

        } catch (\Exception $e) {
            Log::error('Handle Reservation Payment Success Failed', [
                'payment_id' => $payment->id,
                'message'    => $e->getMessage(),
                'data'       => $reservationData,
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function handleOrderPaymentSuccess(Payment $payment, array $orderData, array $orderItems): array
    {
        try {
            $order = DB::transaction(function () use ($payment, $orderData, $orderItems) {
                $roomId = $orderData['room_id'] ?? null;
                $room = $roomId ? \App\Models\Room::withoutGlobalScopes()->find($roomId) : null;
                $hotelId = $payment->hotel_id 
                    ?? ($room ? $room->hotel_id : null) 
                    ?? ($payment->metadata['hotel_id'] ?? null)
                    ?? ($orderData['hotel_id'] ?? null)
                    ?? app(\App\Services\TenantContext::class)->getHotelId();

                if ($hotelId) {
                    app(\App\Services\TenantContext::class)->setHotelId($hotelId);
                }

                $createdOrder = Order::create([
                    'hotel_id'         => $hotelId,
                    'order_number'     => Order::generateOrderNumber($hotelId),
                    'guest_id'         => $orderData['guest_id'],
                    'room_id'          => $orderData['room_id'] ?? null,
                    'order_type'       => Order::TYPE_ROOM_SERVICE,
                    'order_time'       => now(),
                    'status'           => Order::STATUS_PENDING,
                    'source'           => 'guest_qr',
                    'payment_type'     => 'chapa',
                    'subtotal'         => $orderData['subtotal'] ?? $payment->amount,
                    'tax'              => $orderData['tax'] ?? 0,
                    'discount'         => $orderData['discount'] ?? 0,
                    'total'            => $payment->amount,
                    'notes'            => $orderData['notes'] ?? null,
                    'special_requests' => $orderData['special_requests'] ?? null,
                ]);

                foreach ($orderItems as $item) {
                    $itemPrice = $item['price'] ?? ($item['item_price_at_order'] ?? 0);
                    $quantity = $item['quantity'] ?? 1;
                    $lineTotal = $item['total'] ?? ($item['line_total'] ?? ($itemPrice * $quantity));

                    $createdOrder->orderItems()->create([
                        'menu_item_id'        => $item['menu_item_id'],
                        'quantity'            => $quantity,
                        'item_price_at_order' => $itemPrice,
                        'subtotal'            => $lineTotal,
                        'total'               => $lineTotal,
                        'line_total'          => $lineTotal,
                        'notes'               => $item['special_instructions'] ?? ($item['notes'] ?? null),
                    ]);
                }

                $payment->update(['order_id' => $createdOrder->id]);

                Log::info('Order Created After Payment', [
                    'payment_id' => $payment->id,
                    'order_id'   => $createdOrder->id,
                    'guest_id'   => $orderData['guest_id'],
                    'total'      => $payment->amount,
                ]);

                return $createdOrder;
            });

            try {
                $targetHotelId = $order->hotel_id;
                $chefs = \App\Models\User::where('role', 'chef')
                    ->when($targetHotelId, function ($q) use ($targetHotelId) {
                        $q->where(function ($sub) use ($targetHotelId) {
                            $sub->whereHas('hotelMemberships', fn ($hq) => $hq->where('hotel_id', $targetHotelId))
                                ->orDoesntHave('hotelMemberships');
                        });
                    })
                    ->get();

                $roomNumber = $order->room?->room_number ?? 'Room Service';
                foreach ($chefs as $chef) {
                    \App\Models\Notification::create([
                        'user_id' => $chef->id,
                        'type' => 'order_created',
                        'title' => 'New Room Order',
                        'message' => 'Room order #' . $order->order_number . ' (Room ' . $roomNumber . ') has been received for processing.',
                        'read' => false,
                    ]);
                }
            } catch (\Throwable $notifyErr) {
                Log::warning('Failed to notify chefs for room order: ' . $notifyErr->getMessage());
            }

            try {
                app(\App\Services\Waiter\AutomaticWaiterAssignmentService::class)->assignWaiterToReadyOrder($order);
            } catch (\Throwable $assignErr) {
                Log::warning('Automatic waiter assignment after payment failed: ' . $assignErr->getMessage());
            }

            return [
                'success' => true,
                'order'   => $order,
                'message' => 'Order created successfully',
            ];

        } catch (\Exception $e) {
            Log::error('Handle Order Payment Success Failed', [
                'payment_id' => $payment->id,
                'message'    => $e->getMessage(),
                'data'       => $orderData,
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function getStatistics(array $filters = []): array
    {
        try {
            $query = Payment::query();

            if (!empty($filters['from_date']) && !empty($filters['to_date'])) {
                $query->whereBetween('created_at', [
                    $filters['from_date'],
                    $filters['to_date'],
                ]);
            }

            if (!empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            if (!empty($filters['provider'])) {
                $query->where('payment_provider', $filters['provider']);
            }

            $payments = $query->get();

            return [
                'total_payments'     => $payments->count(),
                'total_amount'       => $payments->sum('amount'),
                'verified_payments'  => $payments->where('status', Payment::STATUS_VERIFIED)->count(),
                'verified_amount'    => $payments->where('status', Payment::STATUS_VERIFIED)->sum('amount'),
                'failed_payments'    => $payments->where('status', Payment::STATUS_FAILED)->count(),
                'pending_payments'   => $payments->where('status', Payment::STATUS_PENDING)->count(),
                'average_amount'     => $payments->count() > 0 ? $payments->sum('amount') / $payments->count() : 0,
                'status_breakdown'   => $payments->groupBy('status')->map->count(),
            ];

        } catch (\Exception $e) {
            Log::error('Get Payment Statistics Failed', [
                'message' => $e->getMessage(),
                'filters' => $filters,
            ]);

            throw $e;
        }
    }

    public function generatePaymentReference(Payment $payment): string
    {
        return sprintf(
            '%s-%s-%s',
            strtoupper($payment->payment_provider),
            $payment->tx_ref,
            $payment->id
        );
    }
}
