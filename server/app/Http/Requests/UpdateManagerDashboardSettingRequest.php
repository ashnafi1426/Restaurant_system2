<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateManagerDashboardSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'show_revenue' => [
                'sometimes',
                'boolean',
            ],

            'show_rooms' => [
                'sometimes',
                'boolean',
            ],

            'show_orders' => [
                'sometimes',
                'boolean',
            ],

            'show_housekeeping' => [
                'sometimes',
                'boolean',
            ],

            'show_laundry' => [
                'sometimes',
                'boolean',
            ],

            'show_notifications' => [
                'sometimes',
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