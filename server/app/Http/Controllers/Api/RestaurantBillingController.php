<?php

namespace App\Http\Controllers;

use App\Models\RestaurantCharge;
use Illuminate\Http\JsonResponse;
use App\Services\RestaurantChargeService;
use App\Http\Resources\RestaurantChargeResource;
use App\Http\Resources\RestaurantChargeCollection;

class RestaurantBillingController extends Controller
{
    protected RestaurantChargeService $restaurantChargeService;
    public function __construct(
        RestaurantChargeService $restaurantChargeService
    ) {
        $this->restaurantChargeService = $restaurantChargeService;
    }
    public function index(): RestaurantChargeCollection
    {
        $charges = $this->restaurantChargeService->all();

        return new RestaurantChargeCollection($charges);
    }

    public function show(
        RestaurantCharge $restaurantCharge
    ): RestaurantChargeResource {

        $charge = $this->restaurantChargeService->find(
            $restaurantCharge->id
        );

        return new RestaurantChargeResource($charge);
    }
public function reservationCharges(
    string $reservationId
): RestaurantChargeCollection {

    $charges = $this->restaurantChargeService
        ->reservationCharges($reservationId);

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
public function markPaid(
    RestaurantCharge $restaurantCharge
): RestaurantChargeResource {

    $reference = request()->input('payment_reference');

    $charge = $this->restaurantChargeService->markPaid(
        $restaurantCharge,
        $reference
    );

    return new RestaurantChargeResource($charge);
}

public function cancel(
    RestaurantCharge $restaurantCharge
): RestaurantChargeResource {

    $charge = $this->restaurantChargeService->cancel(
        $restaurantCharge
    );

    return new RestaurantChargeResource($charge);
}
}