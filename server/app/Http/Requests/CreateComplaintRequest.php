<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateComplaintRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role === 'manager';
    }

    public function rules(): array
    {
        return [
            'guest_id' => 'required|exists:users,id',
            'type' => 'required|in:food,room,housekeeping,laundry,reception,restaurant,staff,maintenance,payment',
            'department' => 'required|string',
            'severity' => 'required|in:low,normal,high,critical',
            'description' => 'required|string|min:10|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'guest_id.required' => 'Guest ID is required',
            'guest_id.exists' => 'Guest not found',
            'type.required' => 'Complaint type is required',
            'type.in' => 'Invalid complaint type',
            'department.required' => 'Department is required',
            'severity.required' => 'Severity level is required',
            'severity.in' => 'Invalid severity level',
            'description.required' => 'Description is required',
            'description.min' => 'Description must be at least 10 characters',
            'description.max' => 'Description cannot exceed 1000 characters',
        ];
    }
}

