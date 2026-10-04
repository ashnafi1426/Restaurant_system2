<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Hotel extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'hotels';
    public $incrementing = false;
    protected $keyType = 'string';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'name',
        'slug',
        'email',
        'phone',
        'address',
        'city',
        'country',
        'logo',
        'timezone',
        'currency',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isSuspended(): bool
    {
        return $this->status === self::STATUS_SUSPENDED;
    }

    public function isInactive(): bool
    {
        return $this->status === self::STATUS_INACTIVE;
    }

    public function isArchived(): bool
    {
        return $this->status === self::STATUS_ARCHIVED;
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'hotel_users', 'hotel_id', 'user_id')
            ->withPivot(['role', 'is_active'])
            ->withTimestamps();
    }

    public function memberships()
    {
        return $this->hasMany(HotelUser::class, 'hotel_id');
    }

    public function roles()
    {
        return $this->hasMany(Role::class, 'hotel_id');
    }

    public function floors()
    {
        return $this->hasMany(Floor::class, 'hotel_id');
    }

    public function rooms()
    {
        return $this->hasMany(Room::class, 'hotel_id');
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class, 'hotel_id');
    }
}
