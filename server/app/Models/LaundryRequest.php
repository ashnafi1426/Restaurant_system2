<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaundryRequest extends Model
{
    use HasFactory;

    protected $table = 'laundry_requests';

    protected $fillable = [
        'room_id',
        'guest_id',
        'status',
        'items',
        'requested_time',
        'pickup_time',
        'delivery_time',
        'cost',
        'notes',
    ];

    protected $casts = [
        'requested_time' => 'datetime',
        'pickup_time' => 'datetime',
        'delivery_time' => 'datetime',
        'items' => 'array',
        'cost' => 'decimal:2',
    ];

    protected $keyType = 'string'; // For UUID
    public $incrementing = false;

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id', 'id');
    }

    public function guest()
    {
        return $this->belongsTo(Guest::class, 'guest_id', 'id');
    }
}
