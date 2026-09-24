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
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id', 'id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'chef_id', 'id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isOnBreak(): bool
    {
        return $this->status === 'on_break';
    }

    public function isSeniorChef(): bool
    {
        return in_array($this->rank, ['head_chef', 'executive_chef']);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByRank($query, $rank)
    {
        return $query->where('rank', $rank);
    }
}
