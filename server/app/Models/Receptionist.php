<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Receptionist extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'id',
        'employee_code',
        'shift',
        'bio',
        'profile_photo',
        'hire_date',
        'status',
        'desk_number',
    ];

    protected $casts = [
        'hire_date' => 'date',
    ];

    /**
     * Get the user associated with this receptionist
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id', 'id');
    }

    /**
     * Check if receptionist is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if receptionist is on break
     */
    public function isOnBreak(): bool
    {
        return $this->status === 'on_break';
    }

    /**
     * Scope to get active receptionists
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
