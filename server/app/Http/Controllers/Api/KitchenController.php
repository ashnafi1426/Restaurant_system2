<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\KitchenOrderResource;
use App\Models\Order;
use App\Services\KitchenService;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class KitchenController extends Controller
{
    protected KitchenService $kitchenService;

    public function __construct(KitchenService $kitchenService)
    {
        $this->kitchenService = $kitchenService;
    }

    private function resolveTenant(Request $request): ?string
    {
        $hotelId = $request->input('hotel_id') 
            ?: $request->header('X-Hotel-ID') 
            ?: app(TenantContext::class)->getHotelId();

        if ($hotelId) {
            app(TenantContext::class)->setHotelId($hotelId);
        }

        return $hotelId;
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $this->resolveTenant($request);
            $orders = $this->kitchenService->getKitchenOrders(auth()->user());

            return response()->json([
                'success' => true,
                'message' => 'Kitchen orders retrieved successfully.',
                'data' => [
                    'pending' => KitchenOrderResource::collection($orders['pending']),
                    'preparing' => KitchenOrderResource::collection($orders['preparing']),
                    'ready' => KitchenOrderResource::collection($orders['ready']),
                    'served' => KitchenOrderResource::collection($orders['served']),
                ]
            ]);
        } catch (Throwable $e) {
            \Log::error('[KITCHEN] Failed to load orders: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to load kitchen orders.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function start(Order $order): JsonResponse
    {
        try {
            $updatedOrder = $this->kitchenService->startPreparing($order);

            return response()->json([
                'success' => true,
                'message' => 'Order started preparing successfully.',
                'data' => new KitchenOrderResource($updatedOrder)
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function ready(Order $order): JsonResponse
    {
        try {
            $updatedOrder = $this->kitchenService->markReady($order);

            return response()->json([
                'success' => true,
                'message' => 'Order marked as ready.',
                'data' => new KitchenOrderResource($updatedOrder)
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function complete(Order $order): JsonResponse
    {
        try {
            $updatedOrder = $this->kitchenService->markServed($order);

            return response()->json([
                'success' => true,
                'message' => 'Order completed successfully.',
                'data' => new KitchenOrderResource($updatedOrder)
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function statistics(Request $request): JsonResponse
    {
        try {
            $this->resolveTenant($request);
            $statistics = $this->kitchenService->statistics(auth()->user());

            return response()->json([
                'success' => true,
                'data' => $statistics
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}