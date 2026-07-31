<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateManagerNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'title' => [
                'sometimes',
                'string',
                'max:255',
            ],

            'message' => [
                'sometimes',
                'string',
            ],

            'type' => [
                'sometimes',
                'in:info,success,warning,danger',
            ],

            'is_read' => [
                'sometimes',
                'boolean',
            ],

            'read_at' => [
                'nullable',
                'date',
            ],

        ];
    }
}