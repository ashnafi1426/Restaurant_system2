<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HousekeepingTask extends Model
{
    use HasFactory;

    protected $table = 'housekeeping_tasks';

    protected $fillable = [
        'room_id',
        'assigned_to',
        'status',
        'task_type',
        'description',
        'priority',
        'scheduled_time',
        'completed_time',
        'notes',
    ];

    protected $casts = [
        'scheduled_time' => 'datetime',
        'completed_time' => 'datetime',
    ];

    protected $keyType = 'string'; // For UUID
    public $incrementing = false;

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id', 'id');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to', 'id');
    }
}
