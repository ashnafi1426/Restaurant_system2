<?php

namespace App\Services;

use App\Models\CheckIn;
use App\Models\DeliveryTask;
use App\Models\Floor;
use App\Models\Order;
use App\Models\Reservation;
use App\Models\Room;
use App\Services\TenantContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class RoomService
{
    /**
     * Build base query for rooms filtered by tenant hotel and user search.
     */
    public function getRoomsQuery(array $filters = []): Builder
    {
        $query = Room::select([
            'id', 'hotel_id', 'room_number', 'room_type_id', 'floor_id',
            'floor', 'description', 'status', 'is_active', 'qr_token',
            'qr_image_path', 'qr_generated_at', 'created_at', 'updated_at'
        ])->with([
            'roomType:id,name,base_price_per_night,capacity,hotel_id,is_active',
            'hotel:id,name,city',
            'floor:id,floor_number,name,hotel_id'
        ]);

        if (!empty($filters['search'])) {
            $search = strtolower(trim($filters['search']));
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(room_number) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(description) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(status) LIKE ?', ["%{$search}%"])
                  ->orWhere('floor', 'LIKE', "%{$search}%");
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['room_type_id'])) {
            $query->where('room_type_id', $filters['room_type_id']);
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        return $query->latest();
    }

    /**
     * Get paginated rooms list.
     */
    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return $this->getRoomsQuery($filters)->paginate($perPage);
    }

    /**
     * Create a new room with synchronized floor attributes and tenant scoping.
     */
    public function createRoom(array $data, ?string $hotelId = null): Room
    {
        $hotelId = $hotelId ?: TenantContext::id();

        if ($hotelId) {
            $data['hotel_id'] = $hotelId;
        }

        $data = $this->syncFloorData($data, $hotelId);

        return DB::transaction(function () use ($data) {
            $room = Room::create($data);

            return $room->load(['roomType', 'hotel', 'floor']);
        });
    }

    /**
     * Update room details with synchronized floor attributes.
     */
    public function updateRoom(Room $room, array $data): Room
    {
        $hotelId = $room->hotel_id ?: TenantContext::id();
        $data = $this->syncFloorData($data, $hotelId);

        return DB::transaction(function () use ($room, $data) {
            $room->update($data);

            return $room->fresh()->load(['roomType', 'hotel', 'floor']);
        });
    }

    /**
     * Check if a room can be safely deleted or if blockers exist.
     */
    public function checkDeleteBlockers(Room $room): array
    {
        // 1. Current checked-in guest
        $activeCheckIn = CheckIn::where('room_id', $room->id)
            ->whereNull('checked_out_at')
            ->first();

        if ($activeCheckIn) {
            return [
                'blocked' => true,
                'can_force' => true,
                'message' => "Room #{$room->room_number} has a guest currently checked in. Check out the guest or use Force Delete to remove the room.",
            ];
        }

        // 2. Confirmed or checked-in bookings
        $activeReservation = Reservation::where('room_id', $room->id)
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->first();

        if ($activeReservation) {
            return [
                'blocked' => true,
                'can_force' => true,
                'message' => "Room #{$room->room_number} has an active booking (#{$activeReservation->booking_reference}). Cancel the booking or use Force Delete to remove the room.",
            ];
        }

        // 3. Historical associations
        $hasHistorical = Reservation::where('room_id', $room->id)->exists()
            || CheckIn::where('room_id', $room->id)->exists()
            || Order::where('room_id', $room->id)->exists();

        if ($hasHistorical) {
            return [
                'blocked' => true,
                'can_force' => true,
                'has_historical_data' => true,
                'message' => "Room #{$room->room_number} has associated booking/order records. You can deactivate the room to preserve history, or force delete to completely remove it.",
            ];
        }

        return ['blocked' => false];
    }
    public function deleteRoom(Room $room, bool $force = false): void
    {
        if ($force) {
            $this->forceDeleteRoom($room);
            return;
        }

        $check = $this->checkDeleteBlockers($room);
        if ($check['blocked']) {
            throw new InvalidArgumentException($check['message']);
        }

        $room->delete();
    }

    /**
     * Cascade-delete or decouple related records and delete the room.
     */
    protected function forceDeleteRoom(Room $room): void
    {
        DB::transaction(function () use ($room) {
            $resIds = Reservation::where('room_id', $room->id)->pluck('id')->toArray();
            $checkInIds = CheckIn::where('room_id', $room->id)->pluck('id')->toArray();

            // Decouple orders & payments
            Order::where('room_id', $room->id)
                ->orWhereIn('reservation_id', $resIds)
                ->update(['room_id' => null, 'reservation_id' => null]);

            if (Schema::hasTable('invoices')) {
                $invoiceIds = DB::table('invoices')
                    ->where('room_id', $room->id)
                    ->orWhereIn('reservation_id', $resIds)
                    ->pluck('id')
                    ->toArray();

                if (Schema::hasTable('payments')) {
                    DB::table('payments')
                        ->whereIn('invoice_id', $invoiceIds)
                        ->orWhereIn('reservation_id', $resIds)
                        ->update(['invoice_id' => null, 'reservation_id' => null]);
                }

                if (Schema::hasTable('invoice_items') && !empty($invoiceIds)) {
                    DB::table('invoice_items')->whereIn('invoice_id', $invoiceIds)->delete();
                }

                DB::table('invoices')
                    ->where('room_id', $room->id)
                    ->orWhereIn('reservation_id', $resIds)
                    ->delete();
            }

            if (Schema::hasTable('check_outs')) {
                DB::table('check_outs')
                    ->where('room_id', $room->id)
                    ->orWhereIn('reservation_id', $resIds)
                    ->orWhereIn('check_in_id', $checkInIds)
                    ->delete();
            }

            if (Schema::hasTable('reservation_audit_logs') && !empty($resIds)) {
                DB::table('reservation_audit_logs')->whereIn('reservation_id', $resIds)->delete();
            }

            if (Schema::hasTable('delivery_tasks')) {
                DeliveryTask::where('room_id', $room->id)->delete();
            }

            if (Schema::hasTable('delivery_logs')) {
                DB::table('delivery_logs')->where('room_id', $room->id)->update(['room_id' => null]);
            }

            if (Schema::hasTable('room_service_deliveries')) {
                DB::table('room_service_deliveries')->where('room_id', $room->id)->delete();
            }

            if (Schema::hasTable('housekeeping_tasks')) {
                DB::table('housekeeping_tasks')->where('room_id', $room->id)->delete();
            }

            if (Schema::hasTable('laundry_requests')) {
                DB::table('laundry_requests')->where('room_id', $room->id)->delete();
            }

            CheckIn::where('room_id', $room->id)->delete();
            Reservation::where('room_id', $room->id)->delete();

            $room->delete();
        });
    }

    /**
     * Toggle room active status.
     */
    public function toggleStatus(Room $room): Room
    {
        $room->is_active = !$room->is_active;
        $room->save();

        return $room;
    }

    /**
     * Keep floor_id and floor (floor number) in sync.
     */
    protected function syncFloorData(array $data, ?string $hotelId = null): array
    {
        if (!empty($data['floor_id'])) {
            $floorNumber = Floor::where('id', $data['floor_id'])->value('floor_number');
            if ($floorNumber !== null) {
                $data['floor'] = $floorNumber;
            }
        } elseif (!empty($data['floor'])) {
            $floorId = Floor::where('floor_number', $data['floor'])
                ->when($hotelId, fn($q) => $q->where(function ($sq) use ($hotelId) {
                    $sq->where('hotel_id', $hotelId)->orWhereNull('hotel_id');
                }))
                ->value('id');

            if ($floorId) {
                $data['floor_id'] = $floorId;
            }
        }

        return $data;
    }
}
