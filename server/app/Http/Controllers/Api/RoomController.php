<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoomRequest;
use App\Http\Requests\UpdateRoomRequest;
use App\Http\Resources\RoomResource;
use App\Models\Room;
use App\Services\RoomService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use InvalidArgumentException;
use Throwable;

class RoomController extends Controller
{
    public function __construct(
        protected RoomService $roomService
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only(['search', 'status', 'room_type_id', 'is_active']);
        $perPage = $request->integer('per_page', 100);

        $rooms = $this->roomService->paginate($filters, $perPage);

        return RoomResource::collection($rooms);
    }

    public function store(StoreRoomRequest $request): JsonResponse
    {
        try {
            $room = $this->roomService->createRoom($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Room created successfully.',
                'data' => new RoomResource($room),
            ], 201);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create room: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(Room $room): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new RoomResource($room->load(['roomType', 'hotel', 'floor'])),
        ]);
    }

    public function update(UpdateRoomRequest $request, Room $room): JsonResponse
    {
        try {
            $updatedRoom = $this->roomService->updateRoom($room, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Room updated successfully.',
                'data' => new RoomResource($updatedRoom),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update room: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Request $request, Room $room): JsonResponse
    {
        $force = $request->boolean('force');

        if (!$force) {
            $blockers = $this->roomService->checkDeleteBlockers($room);
            if ($blockers['blocked']) {
                return response()->json([
                    'success' => false,
                    'can_force' => true,
                    'has_historical_data' => $blockers['has_historical_data'] ?? false,
                    'message' => $blockers['message'],
                ], 422);
            }
        }

        try {
            $roomNumber = $room->room_number;
            $this->roomService->deleteRoom($room, $force);

            return response()->json([
                'success' => true,
                'message' => $force
                    ? "Room #{$roomNumber} force deleted successfully."
                    : "Room #{$roomNumber} deleted successfully.",
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'can_force' => true,
                'message' => $e->getMessage(),
            ], 422);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete room: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function toggleStatus(Room $room): JsonResponse
    {
        $room = $this->roomService->toggleStatus($room);

        return response()->json([
            'success' => true,
            'message' => 'Room status updated successfully.',
            'data' => new RoomResource($room->load(['roomType', 'floor'])),
        ]);
    }
}
