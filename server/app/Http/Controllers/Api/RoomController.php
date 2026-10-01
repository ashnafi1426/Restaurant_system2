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
            $data = $request->validated();
            $hotelId = TenantContext::id()
                ?: $request->header('X-Hotel-ID')
                ?: $request->header('x-hotel-id')
                ?: $request->user()?->hotel_id
                ?: $request->user()?->hotelMemberships()->where('is_active', true)->value('hotel_id');

            // Strictly enforce hotel context
            if ($hotelId) {
                $data['hotel_id'] = $hotelId;
            }

            if (!empty($data['floor_id'])) {
                $floorNumber = \App\Models\Floor::where('id', $data['floor_id'])->value('floor_number');
                if ($floorNumber !== null) {
                    $data['floor'] = $floorNumber;
                }
            }

            $room = Room::create($data);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Room created successfully',
                'data' => new RoomResource($room->load('roomType', 'floor'))
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
    public function show($room)
    {
        $roomModel = $room instanceof Room ? $room : Room::withoutTenant()->find($room);
        if (!$roomModel) {
            return response()->json([
                'success' => false,
                'message' => 'Room not found.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new RoomResource($roomModel->load('roomType', 'hotel', 'floor'))
        ]);
    }
    public function update(UpdateRoomRequest $request, $room)
    {
        DB::beginTransaction();

        try {
            $roomModel = $room instanceof Room ? $room : Room::withoutTenant()->find($room);
            if (!$roomModel) {
                return response()->json([
                    'success' => false,
                    'message' => 'Room not found.'
                ], 404);
            }

            $data = $request->validated();
            if (empty($data['hotel_id']) && !empty($roomModel->hotel_id)) {
                $data['hotel_id'] = $roomModel->hotel_id;
            }

            $roomModel->update($data);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Room updated successfully',
                'data' => new RoomResource($roomModel->load('roomType', 'floor'))
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
    public function destroy(Request $request, $room)
    {
        try {
            $roomModel = $room instanceof Room ? $room : Room::withoutTenant()->find($room);
            if (!$roomModel) {
                return response()->json([
                    'success' => false,
                    'message' => 'Room not found or already deleted.'
                ], 404);
            }

            $force = $request->boolean('force') 
                || $request->input('force') === '1' 
                || $request->input('force') === 'true'
                || $request->input('force') === true
                || $request->query('force') === '1'
                || $request->query('force') === 'true'
                || $request->query('force') === true
                || $request->has('force');

            if ($force) {
                // Administrator explicitly requested force delete:
                // Decouple orders & payments and cascade delete linked room records in transaction
                DB::transaction(function () use ($roomModel) {
                    $resIds = Reservation::where('room_id', $roomModel->id)->pluck('id')->toArray();
                    $checkInIds = CheckIn::where('room_id', $roomModel->id)->pluck('id')->toArray();

                    $invoiceIds = [];
                    if (\Illuminate\Support\Facades\Schema::hasTable('invoices')) {
                        $invoiceIds = \Illuminate\Support\Facades\DB::table('invoices')
                            ->where('room_id', $roomModel->id)
                            ->orWhereIn('reservation_id', $resIds)
                            ->pluck('id')
                            ->toArray();
                    }

                    // 1. Decouple orders & payments
                    \App\Models\Order::where('room_id', $roomModel->id)
                        ->orWhereIn('reservation_id', $resIds)
                        ->update(['room_id' => null, 'reservation_id' => null]);

                    if (\Illuminate\Support\Facades\Schema::hasTable('payments')) {
                        \Illuminate\Support\Facades\DB::table('payments')
                            ->whereIn('invoice_id', $invoiceIds)
                            ->orWhereIn('reservation_id', $resIds)
                            ->update(['invoice_id' => null, 'reservation_id' => null]);
                    }

                    // 2. Delete invoice items & invoices
                    if (\Illuminate\Support\Facades\Schema::hasTable('invoice_items') && !empty($invoiceIds)) {
                        \Illuminate\Support\Facades\DB::table('invoice_items')->whereIn('invoice_id', $invoiceIds)->delete();
                    }
                    if (\Illuminate\Support\Facades\Schema::hasTable('invoices')) {
                        \Illuminate\Support\Facades\DB::table('invoices')
                            ->where('room_id', $roomModel->id)
                            ->orWhereIn('reservation_id', $resIds)
                            ->delete();
                    }

                    // 3. Delete check_outs
                    if (\Illuminate\Support\Facades\Schema::hasTable('check_outs')) {
                        \App\Models\CheckOut::where('room_id', $roomModel->id)
                            ->orWhereIn('reservation_id', $resIds)
                            ->orWhereIn('check_in_id', $checkInIds)
                            ->delete();
                    }

                    // 4. Delete audit logs
                    if (\Illuminate\Support\Facades\Schema::hasTable('reservation_audit_logs') && !empty($resIds)) {
                        \Illuminate\Support\Facades\DB::table('reservation_audit_logs')->whereIn('reservation_id', $resIds)->delete();
                    }

                    // 5. Delete delivery tasks / logs / room services
                    if (\Illuminate\Support\Facades\Schema::hasTable('delivery_tasks')) {
                        \App\Models\DeliveryTask::where('room_id', $roomModel->id)->delete();
                    }
                    if (\Illuminate\Support\Facades\Schema::hasTable('delivery_logs')) {
                        \App\Models\DeliveryLog::where('room_id', $roomModel->id)->update(['room_id' => null]);
                    }
                    if (\Illuminate\Support\Facades\Schema::hasTable('room_service_deliveries')) {
                        \Illuminate\Support\Facades\DB::table('room_service_deliveries')->where('room_id', $roomModel->id)->delete();
                    }
                    if (\Illuminate\Support\Facades\Schema::hasTable('housekeeping_tasks')) {
                        \Illuminate\Support\Facades\DB::table('housekeeping_tasks')->where('room_id', $roomModel->id)->delete();
                    }
                    if (\Illuminate\Support\Facades\Schema::hasTable('laundry_requests')) {
                        \Illuminate\Support\Facades\DB::table('laundry_requests')->where('room_id', $roomModel->id)->delete();
                    }

                    // 6. Delete check_ins and reservations
                    CheckIn::where('room_id', $roomModel->id)->delete();
                    Reservation::where('room_id', $roomModel->id)->delete();

                    // 7. Delete room
                    $roomModel->delete();
                });

                return response()->json([
                    'success' => true,
                    'message' => "Room #{$roomModel->room_number} force deleted successfully."
                ]);
            }

            // Normal delete flow (no force):
            // 1. Check for active current guests
            $activeCheckIn = CheckIn::where('room_id', $roomModel->id)
                ->whereNull('checked_out_at')
                ->first();
            if ($activeCheckIn) {
                return response()->json([
                    'success' => false,
                    'can_force' => true,
                    'message' => "Room #{$roomModel->room_number} has a guest currently checked in. Check out the guest or use Force Delete to remove the room."
                ], 422);
            }

            // 2. Check for active upcoming/confirmed bookings
            $activeReservation = Reservation::where('room_id', $roomModel->id)
                ->whereIn('status', ['confirmed', 'checked_in'])
                ->first();
            if ($activeReservation) {
                return response()->json([
                    'success' => false,
                    'can_force' => true,
                    'message' => "Room #{$roomModel->room_number} has an active booking (#{$activeReservation->booking_reference}). Cancel the booking or use Force Delete to remove the room."
                ], 422);
            }

            // 3. Check for historical or completed records
            $hasAnyRecords = Reservation::where('room_id', $roomModel->id)->exists()
                || CheckIn::where('room_id', $roomModel->id)->exists()
                || \App\Models\Order::where('room_id', $roomModel->id)->exists();

            if ($hasAnyRecords) {
                return response()->json([
                    'success' => false,
                    'can_force' => true,
                    'has_historical_data' => true,
                    'message' => "Room #{$roomModel->room_number} has associated booking/order records. You can deactivate the room to preserve history, or force delete to completely remove it."
                ], 422);
            }

            $roomModel->delete();

            return response()->json([
                'success' => true,
                'message' => "Room #{$roomModel->room_number} deleted successfully."
            ]);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete room due to database constraints: ' . $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete room: ' . $e->getMessage()
            ], 500);
        }
    }
    public function toggleStatus($room)
    {
        $roomModel = $room instanceof Room ? $room : Room::withoutTenant()->find($room);
        if (!$roomModel) {
            return response()->json([
                'success' => false,
                'message' => 'Room not found.'
            ], 404);
        }
        $roomModel->is_active = !$roomModel->is_active;
        $roomModel->save();

        return response()->json([
            'success' => true,
            'message' => 'Room status updated',
            'data' => new RoomResource($roomModel)
        ]);
    }
}

