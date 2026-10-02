<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RestaurantChargeCollection;
use App\Http\Resources\RestaurantChargeResource;
use App\Models\RestaurantCharge;
use App\Services\RestaurantChargeService;
use Illuminate\Http\JsonResponse;

class RestaurantBillingController extends Controller
{
    public function __construct(
        protected RestaurantChargeService $restaurantChargeService
    ) {}

    public function index(): RestaurantChargeCollection
    {
        return new RestaurantChargeCollection($this->restaurantChargeService->all());
    }

    public function show(RestaurantCharge $restaurantCharge): RestaurantChargeResource
    {
        $charge = $this->restaurantChargeService->find($restaurantCharge->id);

        return new RestaurantChargeResource($charge);
    }

    public function reservationCharges(string $reservationId): RestaurantChargeCollection
    {
        $charges = $this->restaurantChargeService->reservationCharges($reservationId);

        return new RestaurantChargeCollection($charges);
    }

    public function statistics(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Restaurant billing statistics retrieved successfully.',
            'data' => $this->restaurantChargeService->statistics(),
        ]);
    }

    public function markPaid(RestaurantCharge $restaurantCharge): RestaurantChargeResource
    {
        $reference = request()->input('payment_reference');
        $charge = $this->restaurantChargeService->markPaid($restaurantCharge, $reference);

        return new RestaurantChargeResource($charge);
    }

    public function cancel(RestaurantCharge $restaurantCharge): RestaurantChargeResource
    {
        $charge = $this->restaurantChargeService->cancel($restaurantCharge);

        return new RestaurantChargeResource($charge);
    }
}