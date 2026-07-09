<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoomRequest;
use App\Http\Requests\UpdateRoomRequest;
use App\Http\Resources\RoomResource;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoomController extends Controller
{
    /*
    |----------------------------------------------------
    | GET /rooms
    |----------------------------------------------------
    */
    public function index(Request $request)
    {
        $query = Room::with('roomType');

        // Only filter by status if explicitly specified in request
        // For admin room management, show all rooms
        // For receptionist reservations, pass status=available
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('room_type_id')) {
            $query->where('room_type_id', $request->room_type_id);
        }

        // Show both active and inactive rooms for admin management
        // Only filter if explicitly requested
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $rooms = $query->latest()->paginate(
            $request->get('per_page', 10)
        );

        return RoomResource::collection($rooms);
    }

    /*
    |----------------------------------------------------
    | POST /rooms
    |----------------------------------------------------
    */
    public function store(StoreRoomRequest $request)
    {
        DB::beginTransaction();

        try {
            $room = Room::create($request->validated());

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Room created successfully',
                'data' => new RoomResource($room->load('roomType'))
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create room',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /*
    |----------------------------------------------------
    | GET /rooms/{room}
    |----------------------------------------------------
    */
    public function show(Room $room)
    {
        return response()->json([
            'success' => true,
            'data' => new RoomResource($room->load('roomType'))
        ]);
    }

    /*
    |----------------------------------------------------
    | PUT /rooms/{room}
    |----------------------------------------------------
    */
    public function update(UpdateRoomRequest $request, Room $room)
    {
        DB::beginTransaction();

        try {
            $room->update($request->validated());

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Room updated successfully',
                'data' => new RoomResource($room->load('roomType'))
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update room',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /*
    |----------------------------------------------------
    | DELETE /rooms/{room}
    |----------------------------------------------------
    */
    public function destroy(Room $room)
    {
        $room->delete();

        return response()->json([
            'success' => true,
            'message' => 'Room deleted successfully'
        ]);
    }

    /*
    |----------------------------------------------------
    | PATCH /rooms/{room}/toggle-status
    |----------------------------------------------------
    */
    public function toggleStatus(Room $room)
    {
        $room->is_active = !$room->is_active;
        $room->save();

        return response()->json([
            'success' => true,
            'message' => 'Room status updated',
            'data' => new RoomResource($room)
        ]);
    }
}