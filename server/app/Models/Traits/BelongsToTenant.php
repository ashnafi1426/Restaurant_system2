<?php

namespace App\Models\Traits;

use App\Models\Hotel;
use App\Models\Scopes\TenantScope;
use App\Services\TenantContext;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope());
        static::creating(function ($model) {
            if (empty($model->hotel_id)) {
                $hotelId = app(TenantContext::class)->getHotelId();
                if ($hotelId) {
                    $model->hotel_id = $hotelId;
                }
            }
        });
    }

    public function hotel()
    {
        return $this->belongsTo(Hotel::class, 'hotel_id');
    }

    public function scopeWithoutTenant($query)
    {
        return $query->withoutGlobalScope(TenantScope::class);
    }
}

