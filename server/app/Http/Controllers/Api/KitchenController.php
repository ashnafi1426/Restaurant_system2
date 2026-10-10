<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\KitchenOrderResource;
use App\Models\Order;
use App\Services\KitchenService;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Throwable;

class KitchenController extends Controller
{
    public function __construct(
        protected KitchenService $kitchenService
    ) {}

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

    private function validateOrderHotelAccess(Order $order, Request $request): void
    {
        $activeHotelId = $this->resolveTenant($request);
        $user = auth()->user();

        if ($user && $user->isPlatformAdmin()) {
            return;
        }

        if ($activeHotelId && $order->hotel_id && $order->hotel_id !== $activeHotelId) {
            abort(403, 'Order does not belong to the active hotel kitchen.');
        }

        if ($user && method_exists($user, 'belongsToHotel') && $order->hotel_id && !$user->belongsToHotel($order->hotel_id)) {
            abort(403, 'You do not have kitchen access for this hotel.');
        }
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

    public function start(Request $request, Order $order): JsonResponse
    {
        try {
            $this->validateOrderHotelAccess($order, $request);
            $updatedOrder = $this->kitchenService->startPreparing($order, auth()->user());

            return response()->json([
                'success' => true,
                'message' => 'Order started preparing successfully.',
                'data' => new KitchenOrderResource($updatedOrder)
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function ready(Request $request, Order $order): JsonResponse
    {
        try {
            $this->validateOrderHotelAccess($order, $request);
            $updatedOrder = $this->kitchenService->markReady($order);

            return response()->json([
                'success' => true,
                'message' => 'Order marked as ready and waiter notified.',
                'data' => new KitchenOrderResource($updatedOrder)
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function complete(Request $request, Order $order): JsonResponse
    {
        try {
            $this->validateOrderHotelAccess($order, $request);
            $updatedOrder = $this->kitchenService->markServed($order);

            return response()->json([
                'success' => true,
                'message' => 'Order completed and marked as served.',
                'data' => new KitchenOrderResource($updatedOrder)
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
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

