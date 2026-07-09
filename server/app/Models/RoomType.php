<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RoomType extends Model
{
    use HasFactory;
    use HasUuids;

    /*
    |--------------------------------------------------------------------------
    | Table
    |--------------------------------------------------------------------------
    */

    protected $table = 'room_types';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [

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

    /*
    |--------------------------------------------------------------------------
    | Relationship
    |--------------------------------------------------------------------------
    |
    | One RoomType
    | hasMany Rooms
    |
    */

    public function rooms()
    {
        return $this->hasMany(
            Room::class,
            'room_type_id',
            'id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Scope : Active Room Types
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where(
            'is_active',
            true
        );
    }
}