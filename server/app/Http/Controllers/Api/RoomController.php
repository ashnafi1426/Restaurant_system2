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
        // Always eager load room_type to avoid N+1 queries
        $query = Room::with('roomType');

        // Search by room number, floor, description, or status
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(room_number) LIKE ?', ['%' . strtolower($search) . '%'])
                  ->orWhereRaw('LOWER(description) LIKE ?', ['%' . strtolower($search) . '%'])
                  ->orWhereRaw('LOWER(status) LIKE ?', ['%' . strtolower($search) . '%'])
                  ->orWhere('floor', 'LIKE', '%' . $search . '%');
            });
        }

        // Only filter by status if explicitly specified in request
        // For admin room management, show all rooms
        // For receptionist reservations, pass status=available
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('room_type_id')) {
            $query->where('room_type_id', $request->input('room_type_id'));
        }

        // Show both active and inactive rooms for admin management
        // Only filter if explicitly requested
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->input('is_active'));
        }

        // For admin views (no pagination specified or high limit), return all rooms
        // This ensures search and filters work on complete dataset
        $perPage = $request->input('per_page', 100); // Default to 100 instead of 10
        
        // If explicitly requesting a small page size, respect it
        if ($request->filled('page') && $request->input('per_page')) {
            $perPage = $request->input('per_page');
        }

        $rooms = $query->latest()->paginate($perPage);

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