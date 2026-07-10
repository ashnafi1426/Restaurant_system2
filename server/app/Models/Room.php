<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Room extends Model
{
    use HasUuids;

    protected $fillable = [
        'room_number',
        'room_type_id',
        'floor',
        'description',
        'status',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
    
    public function roomType()
    {
        return $this->belongsTo(RoomType::class);
    }

    /**
     * Scope to search rooms by multiple criteria
     * Searches: room_number, floor, description, status, room_type name
     */
    public function scopeSearch($query, $searchTerm)
    {
        if (!$searchTerm) {
            return $query;
        }

        return $query->where(function ($q) use ($searchTerm) {
            $q->whereRaw('LOWER(room_number) LIKE ?', ['%' . strtolower($searchTerm) . '%'])
              ->orWhereRaw('LOWER(description) LIKE ?', ['%' . strtolower($searchTerm) . '%'])
              ->orWhereRaw('LOWER(status) LIKE ?', ['%' . strtolower($searchTerm) . '%'])
              ->orWhere('floor', 'LIKE', '%' . $searchTerm . '%')
              ->orWhereHas('roomType', function ($query) use ($searchTerm) {
                  $query->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($searchTerm) . '%']);
              });
        });
    }
}