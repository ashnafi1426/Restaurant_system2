<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use App\Services\TenantContext;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $hotelId = app(TenantContext::class)->getHotelId();

        if ($hotelId) {
            $builder->where($model->getTable() . '.hotel_id', $hotelId);
        }
    }
}

