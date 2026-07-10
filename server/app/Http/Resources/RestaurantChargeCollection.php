<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class RestaurantChargeCollection extends ResourceCollection
{
    /**
     * The resource collected.
     */
    public $collects = RestaurantChargeResource::class;

    /**
     * Transform the collection into an array.
     */
    public function toArray(Request $request): array
    {
        return [

            'data' => $this->collection,

        ];
    }

    /**
     * Additional meta data.
     */
    public function with(Request $request): array
    {
        return [

            'success' => true,

            'message' => 'Restaurant charges retrieved successfully.',

            'total' => $this->collection->count(),

        ];
    }
}