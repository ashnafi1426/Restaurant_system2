<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GuestStoreOrderRequest;
use App\Services\GuestOrderService;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;
use Throwable;

class UnifiedOrderController extends Controller
{
    public function __construct(protected GuestOrderService $guestOrderService) {}

    public function store(GuestStoreOrderRequest $request): JsonResponse
    {
        return $this->createOrder($request);
    }

    public function createOrder(GuestStoreOrderRequest $request): JsonResponse
    {
        try {
            $result = $this->guestOrderService->placeOrder($request->validated());

            return response()->json($result['response'], $result['status_code']);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid order data',
                'message' => $e->getMessage(),
            ], 422);
        } catch (Throwable $e) {
            \Log::error('[UNIFIED ORDER] Error creating order: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Server error',
                'message' => 'Unable to create order.',
            ], 500);
        }
    }

    /**
     * Backward compatibility / test accessibility helper.
     */
    protected function calculateOrderTotal(array $items, ?string $targetHotelId = null): array
    {
        return $this->guestOrderService->calculateOrderTotal($items, $targetHotelId);
    }
}
