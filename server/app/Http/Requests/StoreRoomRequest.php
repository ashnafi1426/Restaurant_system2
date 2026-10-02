<?php

namespace App\Http\Requests;

use App\Models\Floor;
use App\Models\Hotel;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $hotelId = TenantContext::id()
            ?: $this->header('X-Hotel-ID')
            ?: $this->user()?->hotelMemberships()->where('is_active', true)->value('hotel_id')
            ?: Hotel::first()?->id;

        $this->merge(['hotel_id' => $hotelId]);

        if ($this->filled('floor_id')) {
            $floorNumber = Floor::where('id', $this->floor_id)->value('floor_number');
            if ($floorNumber !== null) {
                $this->merge(['floor' => $floorNumber]);
            }
        } elseif ($this->filled('floor')) {
            $floorId = Floor::where('floor_number', $this->floor)
                ->when($hotelId, fn($q) => $q->where(function ($sq) use ($hotelId) {
                    $sq->where('hotel_id', $hotelId)->orWhereNull('hotel_id');
                }))
                ->value('id');

            if ($floorId) {
                $this->merge(['floor_id' => $floorId]);
            }
        }

        if (!$this->has('is_active')) {
            $this->merge(['is_active' => true]);
        }
    }

    public function rules(): array
    {
        $hotelId = $this->input('hotel_id');

        $uniqueRoom = Rule::unique('rooms', 'room_number');
        $existsRoomType = Rule::exists('room_types', 'id');
        $existsFloor = Rule::exists('floors', 'id');

        if ($hotelId) {
            $uniqueRoom = $uniqueRoom->where('hotel_id', $hotelId);
            $existsRoomType = $existsRoomType->where('hotel_id', $hotelId);
            $existsFloor = $existsFloor->where(function ($q) use ($hotelId) {
                $q->where('hotel_id', $hotelId)->orWhereNull('hotel_id');
            });
        }

        return [
            'hotel_id' => ['nullable', 'string'],
            'room_number' => ['required', 'string', 'max:50', $uniqueRoom],
            'floor_id' => ['required', $existsFloor],
            'room_type_id' => ['required', $existsRoomType],
            'floor' => ['nullable', 'integer'],
            'description' => ['nullable', 'string'],
            'status' => [
                'required',
                Rule::in([
                    'available',
                    'reserved',
                    'occupied',
                    'cleaning',
                    'maintenance'
                ])
            ],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'room_number.required' => 'Room number is required.',
            'room_number.unique' => 'This room number is already taken in this hotel.',
            'floor_id.required' => 'Please select a floor.',
            'floor_id.exists' => 'The selected floor is invalid or does not belong to this hotel.',
            'room_type_id.required' => 'Please select a room type.',
            'room_type_id.exists' => 'The selected room type is invalid or does not belong to this hotel.',
            'status.required' => 'Room status is required.',
        ];
    }
}