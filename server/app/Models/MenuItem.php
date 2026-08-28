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
    public function categoryRelation()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }
    public function scopeCategory($query, $category)
    {
        return $query->where('category', $category);
    }
    public function getImageUrlAttribute()
    {
        return $this->image 
            ? asset('storage/' . $this->image)
            : null;
    }
    public function getFormattedPriceAttribute()
    {
        return number_format($this->price, 2);
    }
    public function getStatusAttribute()
    {
        return $this->is_available
            ? 'Available'
            : 'Unavailable';
    }

    /**
     * Menu item has many reviews
     */
    public function reviews()
    {
        return $this->hasMany(MenuItemReview::class, 'menu_item_id');
    }

    /**
     * Get only approved reviews
     */
    public function approvedReviews()
    {
        return $this->reviews()->approved();
    }

    /**
     * Get average rating
     */
    public function getAverageRatingAttribute(): ?float
    {
        $avg = $this->approvedReviews()->avg('rating');
        return $avg ? round($avg, 1) : null;
    }

    /**
     * Get review count
     */
    public function getReviewCountAttribute(): int
    {
        return $this->approvedReviews()->count();
    }

    /**
     * Get rating distribution
     */
    public function getRatingDistributionAttribute(): array
    {
        $distribution = [];
        for ($i = 1; $i <= 5; $i++) {
            $distribution[$i] = $this->approvedReviews()->where('rating', $i)->count();
        }
        return $distribution;
    }
}