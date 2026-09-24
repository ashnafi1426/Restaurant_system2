<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cashier extends Model
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
        'register_number',
    ];

    protected $casts = [
        'hire_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id', 'id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isOnBreak(): bool
    {
        return $this->status === 'on_break';
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
