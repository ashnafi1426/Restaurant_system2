<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use App\Models\Traits\BelongsToTenant;

class RoomType extends Model
{
    use HasFactory, HasUuids, BelongsToTenant;

    protected $table = 'room_types';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'hotel_id',
        'name',
        'description',
        'base_price_per_night',
        'capacity',
        'amenities',
        'is_active',
    ];
    protected $casts = [
        'amenities' => 'array',
        'base_price_per_night' => 'decimal:2',
        'capacity' => 'integer',
        'is_active' => 'boolean',
    ];

    public function rooms()
    {
        return $this->hasMany(
            Room::class,
            'room_type_id',
            'id'
        );
    }

    public function scopeActive($query)
    {
        return $query->where(
            'is_active',
            true
        );
    }
}

