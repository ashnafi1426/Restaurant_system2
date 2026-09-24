<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class RestaurantChargeCollection extends ResourceCollection
{
    public $collects = RestaurantChargeResource::class;

    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
        ];
    }

    public function with(Request $request): array
    {
        return [
            'success' => true,
            'message' => 'Restaurant charges retrieved successfully.',
            'total' => $this->collection->count(),
        ];
    }
}