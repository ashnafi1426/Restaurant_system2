<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'guest_id' => ['nullable', 'string'],
            'order_id' => ['nullable', 'string'],
            'menu_item_id' => ['required', 'string', 'exists:menu_items,id'],
            'rating' => ['required', 'integer', 'between:1,5'],
            'review_text' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('review_text') && $this->review_text) {
            $this->merge([
                'review_text' => strip_tags($this->review_text),
            ]);
        }
    }
}

