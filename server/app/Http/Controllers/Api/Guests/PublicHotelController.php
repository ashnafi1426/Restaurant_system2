<?php

namespace App\Http\Controllers\Api\Guests;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use Illuminate\Http\JsonResponse;

class PublicHotelController extends Controller
{
    public function index(): JsonResponse
    {
        $hotels = Hotel::where('status', 'active')
            ->select('id', 'name', 'slug', 'logo', 'address', 'city', 'country', 'phone', 'email', 'currency', 'status')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $hotels,
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $hotel = Hotel::where('status', 'active')
            ->where(function ($q) use ($slug) {
                $q->where('slug', $slug)->orWhere('id', $slug);
            })
            ->select('id', 'name', 'slug', 'logo', 'address', 'city', 'country', 'phone', 'email', 'currency', 'status')
            ->first();

        if (!$hotel) {
            return response()->json([
                'success' => false,
                'message' => 'Hotel not found or unavailable',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $hotel,
        ]);
    }
}

