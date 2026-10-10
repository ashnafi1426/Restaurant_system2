<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaxRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'code' => ['nullable', 'string', 'max:50'],
            'type' => ['required', 'string', 'in:percentage,fixed,vat,service'],
            'rate' => ['required', 'numeric', 'min:0'],
            'applies_to' => ['required', 'string', 'in:all,food,beverage,food_beverage,room,service,general'],
            'is_active' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }
}

