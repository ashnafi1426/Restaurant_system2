<?php

namespace App\Http\Controllers\Api\Payment;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Reservation;
use App\Services\ChapaService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentGatewayController extends Controller
{
    protected ChapaService $chapaService;

    public function __construct(ChapaService $chapaService)
    {
        $this->chapaService = $chapaService;
    }

    public function handleCallback(Request $request): JsonResponse
    {
        try {
            $txRef = $request->get('tx_ref');

            Log::info('💳 [GATEWAY] Payment callback received', [
                'tx_ref' => $txRef,
                'data' => $request->all(),
            ]);

            if (!$txRef) {
                Log::warning('❌ [GATEWAY] Missing tx_ref in callback');
                return response()->json([
                    'success' => false,
                    'message' => 'Missing transaction reference',
                ], 400);
            }

            $payment = Payment::where('tx_ref', $txRef)->first();

            if (!$payment) {
                Log::warning('❌ [GATEWAY] Payment not found', ['tx_ref' => $txRef]);
                return response()->json([
                    'success' => false,
                    'message' => 'Payment not found',
                ], 404);
            }

            $response = $this->chapaService->verify($txRef);

            if (!$response['success']) {
                Log::error('❌ [GATEWAY] Chapa verification failed', [
                    'tx_ref' => $txRef,
                    'error' => $response['message'] ?? 'Unknown error',
                ]);

                $payment->updatePaymentStatus('failed', $response);
                return response()->json([
                    'success' => false,
                    'message' => 'Payment verification failed',
                ], 400);
            }

            if ($this->chapaService->isSuccessful($response)) {
                $payment->updatePaymentStatus(
                    'completed',
                    $response,
                    $this->chapaService->getTransactionId($response),
                    $this->chapaService->getPaymentMethod($response)
                );

                $this->logPaymentTransaction($payment, 'completed', $response);

                Log::info(' [GATEWAY] Payment completed successfully', [
                    'payment_id' => $payment->id,
                    'tx_ref' => $txRef,
                    'amount' => $payment->amount,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Payment processed successfully',
                    'payment_id' => $payment->id,
                    'status' => 'completed',
                ]);
            } else {
                $payment->updatePaymentStatus('failed', $response);
                $this->logPaymentTransaction($payment, 'failed', $response);

                Log::warning('⚠️ [GATEWAY] Payment not successful', [
                    'tx_ref' => $txRef,
                    'status' => $response['data']['status'] ?? 'unknown',
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Payment was not completed successfully',
                    'status' => 'failed',
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('❌ [GATEWAY] Callback processing exception', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred processing the callback',
            ], 500);
        }
    }

    public function getPaymentStatus(string $paymentId): JsonResponse
    {
        try {
            $payment = Payment::findOrFail($paymentId);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $payment->id,
                    'tx_ref' => $payment->tx_ref,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'status' => $payment->status,
                    'payment_status' => $payment->payment_status ?? $payment->status,
                    'payment_method' => $payment->payment_method,
                    'email' => $payment->email,
                    'phone' => $payment->phone,
                    'verified_at' => $payment->verified_at,
                    'paid_at' => $payment->paid_at,
                    'created_at' => $payment->created_at,
                ],
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('❌ [GATEWAY] Payment not found', ['payment_id' => $paymentId]);

            return response()->json([
                'success' => false,
                'message' => 'Payment not found',
            ], 404);

        } catch (\Exception $e) {
            Log::error('❌ [GATEWAY] Get payment status exception', [
                'message' => $e->getMessage(),
                'payment_id' => $paymentId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
            ], 500);
        }
    }

    public function getPaymentHistory(Request $request): JsonResponse
    {
        try {
            $query = Payment::query();

            if ($request->has('status')) {
                $query->where('payment_status', $request->get('status'));
            }

            if ($request->has('payment_status')) {
                $query->where('payment_status', $request->get('payment_status'));
            }

            if ($request->has('from_date') && $request->has('to_date')) {
                $query->whereBetween('created_at', [
                    $request->get('from_date'),
                    $request->get('to_date'),
                ]);
            }

            if ($request->has('hotel_id')) {
                $query->where('hotel_id', $request->get('hotel_id'));
            }

            if ($request->has('search')) {
                $search = $request->get('search');
                $query->where(function ($q) use ($search) {
                    $q->where('email', 'like', "%{$search}%")
                        ->orWhere('tx_ref', 'like', "%{$search}%");
                });
            }

            $perPage = $request->get('per_page', 50);
            $payments = $query->latest()->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $payments->items(),
                'meta' => [
                    'total' => $payments->total(),
                    'per_page' => $payments->perPage(),
                    'current_page' => $payments->currentPage(),
                    'last_page' => $payments->lastPage(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('❌ [GATEWAY] Get payment history exception', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred retrieving payment history',
            ], 500);
        }
    }

    public function processRefund(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'payment_id' => 'required|exists:payments,id',
                'reason' => 'required|string|max:500',
                'refund_amount' => 'nullable|numeric|min:0',
            ]);

            $payment = Payment::findOrFail($validated['payment_id']);

            if ($payment->status !== 'completed' && $payment->status !== 'verified') {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot refund payment with status: {$payment->status}",
                ], 422);
            }

            if ($payment->payment_status === 'refunded') {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment has already been refunded',
                ], 422);
            }

            $refundAmount = $validated['refund_amount'] ?? $payment->amount;

            if ($refundAmount > $payment->amount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Refund amount cannot exceed payment amount',
                ], 422);
            }

            DB::beginTransaction();

            try {
                $payment->update([
                    'payment_status' => 'refunded',
                    'refunded_at' => now(),
                    'refund_reason' => $validated['reason'],
                    'refund_amount' => $refundAmount,
                ]);

                $this->logPaymentTransaction($payment, 'refunded', [
                    'reason' => $validated['reason'],
                    'refund_amount' => $refundAmount,
                ]);

                if ($payment->reservation) {
                    $payment->reservation->update([
                        'status' => 'cancelled',
                        'cancelled_at' => now(),
                    ]);
                }

                DB::commit();

                Log::info(' [GATEWAY] Refund processed successfully', [
                    'payment_id' => $payment->id,
                    'refund_amount' => $refundAmount,
                    'reason' => $validated['reason'],
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Refund processed successfully',
                    'payment_id' => $payment->id,
                    'refund_amount' => $refundAmount,
                    'status' => 'refunded',
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('❌ [GATEWAY] Refund validation failed', [
                'errors' => $e->errors(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('❌ [GATEWAY] Process refund exception', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred processing the refund',
            ], 500);
        }
    }

    private function logPaymentTransaction(Payment $payment, string $status, array $details = []): void
    {
        try {
            DB::table('payment_transaction_logs')->insert([
                'payment_id' => $payment->id,
                'tx_ref' => $payment->tx_ref,
                'hotel_id' => $payment->hotel_id,
                'status' => $status,
                'amount' => $payment->amount,
                'payment_method' => $payment->payment_method ?? 'unknown',
                'details' => json_encode($details),
                'logged_by' => auth()->id(),
                'logged_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Log::info('[GATEWAY] Payment transaction logged', [
                'payment_id' => $payment->id,
                'status' => $status,
                'amount' => $payment->amount,
            ]);

        } catch (\Exception $e) {
            Log::error('❌ [GATEWAY] Failed to log payment transaction', [
                'message' => $e->getMessage(),
                'payment_id' => $payment->id,
            ]);
        }
    }

    public function getRevenueReport(Request $request): JsonResponse
    {
        try {
            $query = Payment::where('payment_status', 'completed');

            if ($request->has('hotel_id')) {
                $query->where('hotel_id', $request->get('hotel_id'));
            }

            if ($request->has('from_date') && $request->has('to_date')) {
                $query->whereBetween('created_at', [
                    $request->get('from_date'),
                    $request->get('to_date'),
                ]);
            }

            $totalRevenue = $query->sum('amount');
            $paymentCount = $query->count();
            $averagePayment = $paymentCount > 0 ? $totalRevenue / $paymentCount : 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'total_revenue' => (float) $totalRevenue,
                    'payment_count' => $paymentCount,
                    'average_payment' => (float) $averagePayment,
                    'currency' => 'ETB',
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('❌ [GATEWAY] Revenue report exception', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred generating revenue report',
            ], 500);
        }
    }
}

