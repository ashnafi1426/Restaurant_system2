<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Facades\Storage;

class MenuItem extends Model
{
    use HasFactory, HasUuids;
    public $incrementing = false;
    protected $keyType = 'string';

    
    protected $table = 'menu_items';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [

        'name',

        'description',

        'category',

        'category_id',

        'price',

        'image',

        'is_available',

    ];
    protected $casts = [

        'price' => 'decimal:2',

        'is_available' => 'boolean',

    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Menu item belongs to a category
     */
    public function categoryRelation()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Available menu items only.
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    /**
     * Filter by category.
     */
    public function scopeCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    /**
     * Return full image URL.
     */
    public function getImageUrlAttribute()
    {
        return $this->image 
            ? asset('storage/' . $this->image)
            : null;
    }

    /**
     * Return formatted price.
     */
    public function getFormattedPriceAttribute()
    {
        return number_format($this->price, 2);
    }

    /**
     * Return status text.
     */
    public function getStatusAttribute()
    {
        return $this->is_available
            ? 'Available'
            : 'Unavailable';
    }
}