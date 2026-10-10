<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Models\Traits\BelongsToTenant;

class TaxRate extends Model
{
    use HasFactory, HasUuids, BelongsToTenant;

    protected $table = 'tax_rates';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'hotel_id',
        'name',
        'code',
        'type',
        'rate',
        'applies_to',
        'is_active',
        'is_default',
        'description',
    ];

    protected $casts = [
        'rate' => 'decimal:2',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    public function menuItems()
    {
        return $this->hasMany(MenuItem::class, 'tax_rate_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFoodBeverage($query)
    {
        return $query->whereIn('applies_to', ['food_beverage', 'general']);
    }
}

