<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Chef extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'id',
        'employee_code',
        'specialization',
        'shift',
        'experience_years',
        'bio',
        'profile_photo',
        'hire_date',
        'status',
        'rank',
    ];

    protected $casts = [
        'hire_date' => 'date',
        'experience_years' => 'integer',
    ];

    /**
     * Get the user associated with this chef
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id', 'id');
    }

    /**
     * Get all orders prepared by this chef
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'chef_id', 'id');
    }

    /**
     * Check if chef is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if chef is on break
     */
    public function isOnBreak(): bool
    {
        return $this->status === 'on_break';
    }

    /**
     * Check if chef is head chef or above
     */
    public function isSeniorChef(): bool
    {
        return in_array($this->rank, ['head_chef', 'executive_chef']);
    }

    /**
     * Scope to get active chefs
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get chefs by rank
     */
    public function scopeByRank($query, $rank)
    {
        return $query->where('rank', $rank);
    }
}
