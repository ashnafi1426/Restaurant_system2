<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Http\Resources\OrderCollection;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\orderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(protected orderService $orderService) {}

    /**
     * Display a listing of orders.
     */
    public function index(Request $request): OrderCollection
    {
        $orders = $this->orderService->index(
            $request->integer('per_page', 15)
        );

        return new OrderCollection($orders);
    }

    /**
     * Store a newly created order.
     */
    public function store(
        StoreOrderRequest $request
    ): JsonResponse {

        $order = $this->orderService->create(
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Order created successfully.',
            'data' => new OrderResource($order),
        ], 201);
    }

    /**
     * Display the specified order.
     */
    public function show(string $id): OrderResource
    {
        $order = $this->orderService->show($id);

        return new OrderResource($order);
    }

    /**
     * Update the specified order.
     */
    public function update(
        UpdateOrderRequest $request,
        string $id
    ): JsonResponse {

        $order = $this->orderService->update(
            $id,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Order updated successfully.',
            'data' => new OrderResource($order),
        ]);
    }

    /**
     * Cancel an order.
     */
    public function destroy(string $id): JsonResponse
    {
        $this->orderService->cancel($id);

        return response()->json([
            'success' => true,
            'message' => 'Order cancelled successfully.',
        ]);
    }

    /**
     * Change order status.
     */
    public function changeStatus(
        Request $request,
        string $id
    ): JsonResponse {

        $request->validate([
            'status' => [
                'required',
                'in:pending,preparing,ready,served,cancelled',
            ],
        ]);

        $order = $this->orderService->changeStatus(
            $id,
            $request->string('status')->toString()
        );

        return response()->json([
            'success' => true,
            'message' => 'Order status updated successfully.',
            'data' => new OrderResource($order),
        ]);
    }
}