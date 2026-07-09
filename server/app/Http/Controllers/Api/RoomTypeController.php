<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoomTypeRequest;
use App\Http\Requests\UpdateRoomTypeRequest;
use App\Http\Resources\RoomTypeResource;
use App\Models\RoomType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoomTypeController extends Controller{
    public function index(Request $request)
    {
        $query = RoomType::query();
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where(
                    'name',
                    'like',
                    "%{$search}%"
                )
                ->orWhere(
                    'description',
                    'like',
                    "%{$search}%"
                );
            });
        }
        if ($request->filled('is_active')) {
            $query->where(
                'is_active',
                $request->boolean('is_active')

            );
        }
        $query->withCount('rooms');
        $query->latest();
        $roomTypes = $query->paginate(
            $request->get('per_page', 10)

        );
        return RoomTypeResource::collection(

            $roomTypes

        );
    }
    public function store(StoreRoomTypeRequest $request)
    {
        DB::beginTransaction();

        try {
            $roomType = RoomType::create([
                'name' => $request->name,
                'description' => $request->description,
                'base_price_per_night' => $request->base_price_per_night,
                'capacity' => $request->capacity,
                'amenities' => $request->amenities,
                'is_active' => $request->is_active,
            ]);
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Room type created successfully.',
                'data' => new RoomTypeResource($roomType)
            ], 201);
        } catch (\Exception $exception) {
            DB::rollBack();
            return response()->json([

                'success' => false,

                'message' => 'Unable to create room type.',

                'error' => $exception->getMessage()

            ], 500);

        }

    }
    public function show(RoomType $roomType)
    {

        return response()->json([

            'success' => true,

            'message' => 'Room type retrieved successfully.',

            'data' => new RoomTypeResource($roomType)

        ], 200);

    }
    public function update(
        UpdateRoomTypeRequest $request,
        RoomType $roomType
    )
    {

        DB::beginTransaction();

        try {
            $roomType->name = $request->name;

            $roomType->description = $request->description;

            $roomType->base_price_per_night =
                $request->base_price_per_night;

            $roomType->capacity =
                $request->capacity;

            $roomType->amenities =
                $request->amenities;

            $roomType->is_active =
                $request->is_active;

            /*
            |--------------------------------------------------------------------------
            | Save
            |--------------------------------------------------------------------------
            */

            $roomType->save();
            DB::commit();
            return response()->json([

                'success' => true,

                'message' => 'Room type updated successfully.',

                'data' => new RoomTypeResource($roomType)

            ], 200);

        }

        catch (\Exception $exception) {

            DB::rollBack();

            return response()->json([

                'success' => false,

                'message' => 'Unable to update room type.',

                'error' => $exception->getMessage()

            ], 500);

        }

    }
public function destroy(RoomType $roomType)
    {
        DB::beginTransaction();

        try {
    if ($roomType->rooms()->exists()) {

                return response()->json([

                    'success' => false,

                    'message' =>
                        'Cannot delete this room type because it has assigned rooms.'

                ], 422);

            }
            $roomType->delete();
            DB::commit();
            return response()->json([

                'success' => true,

                'message' => 'Room type deleted successfully.'

            ], 200);

        }

        catch (\Exception $exception) {

            DB::rollBack();

            return response()->json([

                'success' => false,

                'message' => 'Unable to delete room type.',

                'error' => $exception->getMessage()

            ], 500);

        }

    }
protected function successResponse(

    string $message,

    mixed $data = null,

    int $status = 200

) {

    return response()->json([

        'success' => true,

        'message' => $message,

        'data' => $data

    ], $status);

}/**
 * ------------------------------------------------------------------
 * Standard Error Response
 * ------------------------------------------------------------------
 */

protected function errorResponse(

    string $message,

    mixed $error = null,

    int $status = 500

) {

    return response()->json([

        'success' => false,

        'message' => $message,

        'error' => $error

    ], $status);

}
}
    