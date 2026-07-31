<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreManagerDashboardSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'show_revenue' => [
                'boolean',
            ],

            'show_rooms' => [
                'boolean',
            ],

            'show_orders' => [
                'boolean',
            ],

            'show_housekeeping' => [
                'boolean',
            ],

            'show_laundry' => [
                'boolean',
            ],

            'show_notifications' => [
                'boolean',
            ],

            'theme' => [
                'nullable',
                'string',
                'max:30',
            ],

        ];
    }
}