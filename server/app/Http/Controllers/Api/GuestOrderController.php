<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GuestStoreOrderRequest;
use App\Http\Resources\OrderCollection;
use App\Http\Resources\OrderResource;
use App\Services\Restaurant\GuestOrderService;
use Illuminate\Http\JsonResponse;

class GuestOrderController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected GuestOrderService $guestOrderService
    ) {
    }

    /**
     * Display the restaurant menu for a scanned QR code.
     *
     * GET /api/guest/menu/{token}
     */
    public function menu(string $token): JsonResponse
    {
        $menu = $this->guestOrderService->menu($token);

        return response()->json([
            'success' => true,
            'message' => 'Menu retrieved successfully.',
            'data' => $menu,
        ]);
    }

    /**
     * Create a new guest order.
     *
     * POST /api/guest/orders
     */
    public function store(
        GuestStoreOrderRequest $request
    ): JsonResponse {

        $order = $this->guestOrderService->create(
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Order placed successfully.',
            'data' => new OrderResource($order),
        ], 201);
    }
public function show(
    string $token,
    string $id
): OrderResource
{
    $order = $this->guestOrderService->show(
        $token,
        $id
    );

    return new OrderResource($order);
}

public function history(
    string $token
): OrderCollection
{
    $orders = $this->guestOrderService->history(
        $token
    );

    return new OrderCollection($orders);
}
}