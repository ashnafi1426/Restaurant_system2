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
}