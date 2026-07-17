<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Category extends Model
{
    use HasFactory, HasUuids;
    
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * A category has many menu items
     */
    public function menuItems()
    {
        return $this->hasMany(MenuItem::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Active categories only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Order by display_order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order', 'asc')->orderBy('name', 'asc');
    }

    /*
    |--------------------------------------------------------------------------
    | Mutators
    |--------------------------------------------------------------------------
    */

    /**
     * Generate slug from name before saving
     */
    protected static function booted()
    {
        static::creating(function ($category) {
            if (!$category->slug) {
                $category->slug = \Illuminate\Support\Str::slug($category->name);
            }
        });

        static::updating(function ($category) {
            if ($category->isDirty('name')) {
                $category->slug = \Illuminate\Support\Str::slug($category->name);
            }
        });
    }

    /**
     * Get menu items count
     */
    public function getItemsCountAttribute()
    {
        return $this->menuItems()->count();
    }
}
