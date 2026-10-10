<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateResponseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'response_text' => ['required', 'string', 'max:500'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('response_text') && $this->response_text) {
            $this->merge([
                'response_text' => strip_tags($this->response_text),
            ]);
        }
    }
}

