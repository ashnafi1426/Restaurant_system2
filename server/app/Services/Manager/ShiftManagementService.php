<?php

namespace App\Services\Manager;

use App\Models\HotelShift;
use Illuminate\Support\Facades\Log;
class ShiftManagementService
{
    public function createShift(array $data): HotelShift
    {
        try {
            $shift = HotelShift::create([
                'name' => $data['name'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'status' => $data['status'] ?? 'active',
                'description' => $data['description'] ?? null,
            ]);

            Log::info("Shift created", [
                'shift_id' => $shift->id,
                'name' => $shift->name,
                'start_time' => $shift->start_time,
                'end_time' => $shift->end_time,
            ]);

            return $shift;
        } catch (\Exception $e) {
            Log::error("Failed to create shift: {$e->getMessage()}");
            throw $e;
        }
    }
    public function updateShift(string $shiftId, array $data): HotelShift
    {
        try {
            $shift = HotelShift::findOrFail($shiftId);
            $shift->update(array_filter([
                'name' => $data['name'] ?? null,
                'start_time' => $data['start_time'] ?? null,
                'end_time' => $data['end_time'] ?? null,
                'status' => $data['status'] ?? null,
                'description' => $data['description'] ?? null,
            ], function($value) { return $value !== null; }));

            Log::info("Shift updated", ['shift_id' => $shiftId]);

            return $shift;
        } catch (\Exception $e) {
            Log::error("Failed to update shift: {$e->getMessage()}");
            throw $e;
        }
    }
    public function deactivateShift(string $shiftId): bool
    {
        try {
            HotelShift::findOrFail($shiftId)->update(['status' => 'inactive']);

            Log::info("Shift deactivated", ['shift_id' => $shiftId]);

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to deactivate shift: {$e->getMessage()}");
            return false;
        }
    }
    public function activateShift(string $shiftId): bool
    {
        try {
            HotelShift::findOrFail($shiftId)->update(['status' => 'active']);

            Log::info("Shift activated", ['shift_id' => $shiftId]);

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to activate shift: {$e->getMessage()}");
            return false;
        }
    }
    public function getAllShifts()
    {
        return HotelShift::orderBy('start_time', 'asc')->get();
    }
    public function getActiveShifts()
    {
        return HotelShift::active()->orderBy('start_time', 'asc')->get();
    }
    public function getCurrentShift(): ?HotelShift
    {
        return HotelShift::active()->get()->first(function ($shift) {
            return $shift->isCurrentShift();
        });
    }
    public function getShiftByName(string $name): ?HotelShift
    {
        return HotelShift::where('name', 'like', "%{$name}%")->first();
    }
}
