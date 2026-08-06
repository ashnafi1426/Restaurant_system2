<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Order;
use App\Models\Guest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ============================================================================
 * PaymentService
 * ============================================================================
 * Handles payment-related business logic and operations
 * 
 * Responsibilities:
 * - Create payment records for reservations and orders
 * - Handle post-payment actions (create records in database)
 * - Calculate payment amounts
 * - Generate payment metadata
 * - Query and filter payments
 * ============================================================================
 */
class PaymentService
{
    /**
     * Create Payment for Reservation
     * 
     * Initiates a payment record for hotel reservation booking
     * Does NOT create reservation record yet (happens after payment verification)
     * 
     * @param array $data - Payment data
     *   - amount (required)
     *   - first_name (required)
     *   - last_name (required)
     *   - email (required)
     *   - phone (required)
     *   - guest_id (optional)
     * 
     * @return Payment
     * @throws \Exception
     */
    public function createReservationPayment(array $data): Payment
    {
        try {
            $metadata = $data['metadata'] ?? [];
            $metadata['type'] = 'reservation';
            $metadata['created_at'] = now()->toIso8601String();
            
            // Ensure amount is properly formatted as integer for Chapa (smallest currency unit)
            $amount = (int)($data['amount'] * 100) / 100; // Store as decimal with 2 places
            
            Log::info('Creating Payment Record', [
                'amount' => $amount,
                'amount_original' => $data['amount'],
                'guest_id' => $data['guest_id'] ?? null,
            ]);
            
            $payment = Payment::create([
                'tx_ref'           => (new ChapaService())->generateTransactionReference(),
                'amount'           => $amount,
                'currency'         => 'ETB',
                'first_name'       => $data['first_name'],
                'last_name'        => $data['last_name'],
                'email'            => $data['email'],
                'phone'            => $data['phone'],
                'payment_provider' => Payment::PROVIDER_CHAPA,
                'status'           => Payment::STATUS_PENDING,
                'guest_id'         => $data['guest_id'] ?? null,
                'metadata'         => $metadata,
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

    /**
     * Create Payment for Guest Order (QR Ordering)
     * 
     * Initiates a payment record for guest restaurant order
     * Does NOT create order record yet (happens after payment verification)
     * 
     * @param array $data - Payment data
     *   - amount (required)
     *   - first_name (required)
     *   - last_name (required)
     *   - email (required)
     *   - phone (required)
     *   - guest_id (required)
     *   - room_id (optional)
     * 
     * @return Payment
     * @throws \Exception
     */
    public function createOrderPayment(array $data): Payment
    {
        try {
            // ✅ PRESERVE ALL METADATA - especially items and calculation arrays
            $metadata = $data['metadata'] ?? [];
            
            // Only add/override specific fields without destroying existing data
            $metadata['type'] = 'order';
            $metadata['room_id'] = $data['room_id'] ?? ($metadata['room_id'] ?? null);
            $metadata['created_at'] = now()->toIso8601String();
            
            // ✅ Log to verify items are being saved
            Log::info('💾 [PAYMENT] Creating order payment with metadata', [
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

            // ✅ Verify metadata was saved correctly
            $savedMetadata = $payment->fresh()->metadata;
            Log::info('✅ [PAYMENT] Order Payment Created', [
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

    /**
     * Handle Successful Reservation Payment
     * 
     * Called after payment is verified as successful.
     * Creates the actual Reservation record in database.
     * 
     * Transaction ensures atomicity - if reservation creation fails,
     * payment status is NOT marked as verified.
     * 
     * @param Payment $payment
     * @param array $reservationData
     * 
     * @return array - ['success' => bool, 'reservation' => Reservation, 'message' => string]
     */
    public function handleReservationPaymentSuccess(Payment $payment, array $reservationData): array
    {
        try {
            // Start database transaction
            $reservation = DB::transaction(function () use ($payment, $reservationData) {
                // Create reservation record with 'pending' status
                // Receptionist needs to confirm before it becomes 'confirmed'
                $reservation = Reservation::create([
                    'booking_reference' => Reservation::generateBookingReference(),
                    'guest_id'          => $reservationData['guest_id'],
                    'room_id'           => $reservationData['room_id'],
                    'check_in_date'     => $reservationData['check_in_date'],
                    'check_out_date'    => $reservationData['check_out_date'],
                    'number_of_guests'  => $reservationData['number_of_guests'],
                    'status'            => 'pending',
                    'special_requests'  => $reservationData['special_requests'] ?? null,
                    'created_by'        => auth()->id() ?? null,
                ]);

                // Link payment to reservation
                $payment->update(['reservation_id' => $reservation->id]);

                // Log the creation
                Log::info('Reservation Created After Payment', [
                    'payment_id'     => $payment->id,
                    'reservation_id' => $reservation->id,
                    'guest_id'       => $reservationData['guest_id'],
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

    /**
     * Handle Successful Order Payment
     * 
     * Called after payment is verified as successful.
     * Creates the actual Order record in database.
     * 
     * Transaction ensures atomicity - if order creation fails,
     * payment status is NOT marked as verified.
     * 
     * @param Payment $payment
     * @param array $orderData
     * @param array $orderItems - Array of order items
     * 
     * @return array - ['success' => bool, 'order' => Order, 'message' => string]
     */
    public function handleOrderPaymentSuccess(Payment $payment, array $orderData, array $orderItems): array
    {
        try {
            // Start database transaction
            $order = DB::transaction(function () use ($payment, $orderData, $orderItems) {
                // Create order record
                $order = Order::create([
                    'order_number'    => Order::generateOrderNumber(),
                    'guest_id'        => $orderData['guest_id'],
                    'room_id'         => $orderData['room_id'] ?? null,
                    'order_time'      => now(),
                    'status'          => Order::STATUS_PENDING,
                    'source'          => 'guest_qr',
                    'payment_type'    => 'chapa',
                    'subtotal'        => $orderData['subtotal'] ?? $payment->amount,
                    'tax'             => $orderData['tax'] ?? 0,
                    'discount'        => $orderData['discount'] ?? 0,
                    'total'           => $payment->amount,
                    'notes'           => $orderData['notes'] ?? null,
                    'special_requests' => $orderData['special_requests'] ?? null,
                ]);

                // Create order items
                foreach ($orderItems as $item) {
                    $order->orderItems()->create([
                        'menu_item_id' => $item['menu_item_id'],
                        'quantity'     => $item['quantity'],
                        'price'        => $item['price'],
                        'special_instructions' => $item['special_instructions'] ?? null,
                    ]);
                }

                // Link payment to order
                $payment->update(['order_id' => $order->id]);

                // Log the creation
                Log::info('Order Created After Payment', [
                    'payment_id' => $payment->id,
                    'order_id'   => $order->id,
                    'guest_id'   => $orderData['guest_id'],
                    'total'      => $payment->amount,
                ]);

                return $order;
            });

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

    /**
     * Get Payment Statistics
     * 
     * Returns payment metrics and statistics
     * 
     * @param array $filters - Filter options (date range, status, provider, etc.)
     * 
     * @return array
     */
    public function getStatistics(array $filters = []): array
    {
        try {
            $query = Payment::query();

            // Apply filters
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

    /**
     * Generate Payment Reference for Display
     * 
     * @param Payment $payment
     * 
     * @return string
     */
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
