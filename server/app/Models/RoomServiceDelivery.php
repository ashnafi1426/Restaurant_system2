<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomServiceDelivery extends Model
{
    use HasFactory;

    protected $table = 'room_service_deliveries';

    protected $fillable = [
        'room_id',
        'order_id',
        'status',
        'delivered_by',
        'scheduled_time',
        'delivered_time',
        'notes',
    ];

    protected $casts = [
        'scheduled_time' => 'datetime',
        'delivered_time' => 'datetime',
    ];

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id', 'id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    public function deliveredBy()
    {
        return $this->belongsTo(User::class, 'delivered_by', 'id');
    }
}
