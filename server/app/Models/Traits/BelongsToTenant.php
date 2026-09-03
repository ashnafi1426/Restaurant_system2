<?php

namespace App\Models\Traits;

use App\Models\Hotel;
use App\Models\Scopes\TenantScope;
use App\Services\TenantContext;

trait BelongsToTenant
{
    /**
     * Boot the BelongsToTenant trait.
     */
    protected static function bootBelongsToTenant(): void
    {
        // 1. Add global tenant query scope
        static::addGlobalScope(new TenantScope());

        // 2. Automatically assign hotel_id on model creation if not explicitly provided
        static::creating(function ($model) {
            if (empty($model->hotel_id)) {
                $hotelId = app(TenantContext::class)->getHotelId();
                if ($hotelId) {
                    $model->hotel_id = $hotelId;
                }
            }
        });
    }

    /**
     * Relationship to the owning Hotel tenant.
     */
    public function hotel()
    {
        return $this->belongsTo(Hotel::class, 'hotel_id');
    }

    /**
     * Scope a query to exclude tenant filtering (for super admin or cross-tenant tasks).
     */
    public function scopeWithoutTenant($query)
    {
        return $query->withoutGlobalScope(TenantScope::class);
    }
}
