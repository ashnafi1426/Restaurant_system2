<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateManagerAnnouncementRequest extends FormRequest
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

            'content' => [
                'sometimes',
                'string',
            ],

            'is_active' => [
                'sometimes',
                'boolean',
            ],

            'published_at' => [
                'nullable',
                'date',
            ],

        ];
    }
}