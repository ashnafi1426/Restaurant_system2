<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Order;
use App\Models\Guest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

            $payment = Payment::create([
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
            $reservation = DB::transaction(function () use ($payment, $reservationData) {
                $hotelId = $payment->hotel_id ?? ($payment->metadata['hotel_id'] ?? null) ?? ($reservationData['hotel_id'] ?? null);

                $reservation = Reservation::create([
                    'hotel_id'          => $hotelId,
                    'booking_reference' => Reservation::generateBookingReference(),
                    'guest_id'          => $reservationData['guest_id'],
                    'room_id'           => $reservationData['room_id'],
                    'check_in_date'     => $reservationData['check_in_date'],
                    'check_out_date'    => $reservationData['check_out_date'],
                    'number_of_guests'  => $reservationData['number_of_guests'],
                    'status'            => 'pending',
                    'special_requests'  => $reservationData['special_requests'] ?? null,
                    'total_amount'      => $payment->amount,
                    'created_by'        => auth()->id() ?? null,
                ]);

                $payment->update(['reservation_id' => $reservation->id]);

                Log::info('Reservation Created After Payment', [
                    'payment_id'     => $payment->id,
                    'reservation_id' => $reservation->id,
                    'guest_id'       => $reservationData['guest_id'],
                    'total_amount'   => $payment->amount,
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
                $room = $roomId ? \App\Models\Room::find($roomId) : null;
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
                    'order_number'     => Order::generateOrderNumber(),
                    'guest_id'         => $orderData['guest_id'],
                    'room_id'          => $orderData['room_id'] ?? null,
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
                    $createdOrder->orderItems()->create([
                        'menu_item_id'         => $item['menu_item_id'],
                        'quantity'             => $item['quantity'],
                        'price'                => $item['price'],
                        'special_instructions' => $item['special_instructions'] ?? null,
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
