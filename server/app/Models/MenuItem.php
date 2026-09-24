<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Facades\Storage;
use App\Models\Traits\BelongsToTenant;

class MenuItem extends Model
{
    use HasFactory, HasUuids, BelongsToTenant;
    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'menu_items';
    protected $fillable = [
        'hotel_id',
        'name',
        'description',
        'category',
        'category_id',
        'price',
        'tax_rate_id',
        'tax_included',
        'image',
        'is_available',
    ];
    protected $casts = [
        'price' => 'decimal:2',
        'tax_included' => 'boolean',
        'is_available' => 'boolean',
    ];
    public function categoryRelation()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
    public function taxRate()
    {
        return $this->belongsTo(TaxRate::class, 'tax_rate_id');
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

    public function reviews()
    {
        return $this->hasMany(MenuItemReview::class, 'menu_item_id');
    }

    public function approvedReviews()
    {
        return $this->reviews()->approved();
    }

    public function getAverageRatingAttribute(): ?float
    {
        $avg = $this->approvedReviews()->avg('rating');
        return $avg ? round($avg, 1) : null;
    }

    public function getReviewCountAttribute(): int
    {
        return $this->approvedReviews()->count();
    }

    public function getRatingDistributionAttribute(): array
    {
        $distribution = [];
        for ($i = 1; $i <= 5; $i++) {
            $distribution[$i] = $this->approvedReviews()->where('rating', $i)->count();
        }
        return $distribution;
    }
}