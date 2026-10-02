<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Models\Traits\BelongsToTenant;

class RestaurantSection extends Model
{
    use HasFactory, HasUuids, BelongsToTenant;

    protected $table = 'restaurant_sections';

    protected $fillable = [
        'hotel_id',
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function tables()
    {
        return $this->hasMany(RestaurantTable::class, 'section_id');
    }

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
