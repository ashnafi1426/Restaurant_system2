<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoomRequest;
use App\Http\Requests\UpdateRoomRequest;
use App\Http\Resources\RoomResource;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use App\Models\CheckIn;
use App\Models\Reservation;
use App\Services\TenantContext;
class RoomController extends Controller
{
    public function index(Request $request)
    {
        $query = Room::with('roomType', 'hotel');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(room_number) LIKE ?', ['%' . strtolower($search) . '%'])
                  ->orWhereRaw('LOWER(description) LIKE ?', ['%' . strtolower($search) . '%'])
                  ->orWhereRaw('LOWER(status) LIKE ?', ['%' . strtolower($search) . '%'])
                  ->orWhere('floor', 'LIKE', '%' . $search . '%');
            });
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('room_type_id')) {
            $query->where('room_type_id', $request->input('room_type_id'));
        }
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->input('is_active'));
        }
        $perPage = $request->input('per_page', 100);
        if ($request->filled('page') && $request->input('per_page')) {
            $perPage = $request->input('per_page');
        }

        $rooms = $query->latest()->paginate($perPage);

        return RoomResource::collection($rooms);
    }
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
    public function show(Room $room)
    {
        $currentHotelId =TenantContext::id();
        if ($currentHotelId && $room->hotel_id && $room->hotel_id !== $currentHotelId) {
            abort(404, 'Room not found.');
        }

        return response()->json([
            'success' => true,
            'data' => new RoomResource($room->load('roomType', 'hotel'))
        ]);
    }
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
    public function destroy(Room $room)
    {
        try {
            $hasReservations = Reservation::where('room_id', $room->id)->exists();
            $hasCheckIns = CheckIn::where('room_id', $room->id)->exists();

            if ($hasReservations || $hasCheckIns) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete room because it has associated reservations or check-ins. Deactivate the room instead.'
                ], 422);
            }

            $room->delete();

            return response()->json([
                'success' => true,
                'message' => 'Room deleted successfully'
            ]);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete room due to linked database records (reservations, check-ins, or orders).'
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete room: ' . $e->getMessage()
            ], 500);
        }
    }
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

