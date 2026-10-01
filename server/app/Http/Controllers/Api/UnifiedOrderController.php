<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GuestOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class UnifiedOrderController extends Controller
{
    protected GuestOrderService $guestOrderService;

    public function __construct(GuestOrderService $guestOrderService)
    {
        $this->guestOrderService = $guestOrderService;
    }

    public function store(Request $request): JsonResponse
    {
        return $this->createOrder($request);
    }

    public function createOrder(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'qr_token' => 'required|string|min:8|max:30',
                'items' => 'required|array|min:1',
                'items.*.menu_item_id' => 'required|uuid|exists:menu_items,id',
                'items.*.quantity' => 'required|integer|min:1|max:100',
                'special_requests' => 'nullable|string|max:500',
                'payment_type' => 'nullable|in:room_charge,cash,card',
            ]);

            $result = $this->guestOrderService->placeOrder($validated);

            return response()->json($result['response'], $result['status_code']);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\InvalidArgumentException $e) {
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
