<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MenuItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $imageUrl = null;
        
        if ($this->image) {
            if (filter_var($this->image, FILTER_VALIDATE_URL)) {
                $imageUrl = $this->image;
            } else {
                $imageUrl = asset('storage/' . $this->image);
            }
        }
        
        $price = (float) $this->price;
        $taxRateModel = $this->relationLoaded('taxRate') ? $this->taxRate : $this->taxRate;
        $rate = $taxRateModel ? (float) $taxRateModel->rate : 0.0;
        $taxIncluded = (bool) ($this->tax_included ?? false);

        if ($rate > 0) {
            if ($taxIncluded) {
                $basePrice = round($price / (1 + ($rate / 100)), 2);
                $taxAmount = round($price - $basePrice, 2);
                $totalPrice = $price;
            } else {
                $basePrice = $price;
                $taxAmount = round($price * ($rate / 100), 2);
                $totalPrice = round($price + $taxAmount, 2);
            }
        } else {
            $basePrice = $price;
            $taxAmount = 0.0;
            $totalPrice = $price;
        }
        
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'category' => $this->category,
            'category_id' => $this->category_id,
            'category_name' => $this->relationLoaded('categoryRelation') ? ($this->categoryRelation?->name ?? $this->category) : $this->category,
            'price' => $price,
            'formatted_price' => number_format($price, 2),
            'base_price' => $basePrice,
            'tax_amount' => $taxAmount,
            'total_price' => $totalPrice,
            'formatted_total_price' => number_format($totalPrice, 2),
            'image' => $imageUrl,
            'image_url' => $imageUrl,
            'is_available' => $this->is_available,
            'status' => $this->is_available ? 'Available' : 'Unavailable',
            'tax_rate_id' => $this->tax_rate_id,
            'tax_included' => $taxIncluded,
            'tax_rate' => $taxRateModel ? new TaxRateResource($taxRateModel) : null,
            'created_at' => optional($this->created_at)->format('Y-m-d H:i:s'),
            'updated_at' => optional($this->updated_at)->format('Y-m-d H:i:s'),
        ];
    }
}