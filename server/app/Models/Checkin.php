<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class CheckIn extends Model
{
    use HasUuids;

    protected $table = 'check_ins';

    protected $fillable = [
        'reservation_id',
        'guest_id',
        'room_id',
        'checked_in_at',
        'expected_check_out_at',
        'checked_out_at',
    ];

    protected $casts = [
        'checked_in_at' => 'datetime',
        'expected_check_out_at' => 'datetime',
        'checked_out_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function guest()
    {
        return $this->belongsTo(Guest::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    public function isCheckedOut(): bool
    {
        return !is_null($this->checked_out_at);
    }

    public function isActive(): bool
    {
        return is_null($this->checked_out_at);
    }
}