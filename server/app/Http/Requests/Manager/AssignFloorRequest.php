<?php

namespace App\Http\Requests\Manager;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * AssignFloorRequest
 * 
 * Validates floor assignment data
 * Ensures valid waiter-floor-shift combinations
 */
class AssignFloorRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role === 'manager';
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'assignments' => ['required', 'array', 'min:1'],
            'assignments.*.waiter_id' => [
                'required',
                'integer',
                'exists:waiters,id'
            ],
            'assignments.*.floor_id' => [
                'required',
                'uuid',
                'exists:hotel_floors,id'
            ],
            'assignments.*.shift_id' => [
                'required',
                'uuid',
                'exists:hotel_shifts,id'
            ],
            'assignments.*.assignment_date' => [
                'required',
                'date',
                'after_or_equal:today'
            ],
            'assignments.*.priority' => [
                'required',
                Rule::in(['primary', 'secondary', 'backup']),
            ],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'assignments.required' => 'At least one assignment is required',
            'assignments.min' => 'At least one assignment must be provided',
            'assignments.*.waiter_id.required' => 'Waiter is required',
            'assignments.*.waiter_id.exists' => 'Selected waiter does not exist',
            'assignments.*.floor_id.required' => 'Floor is required',
            'assignments.*.floor_id.exists' => 'Selected floor does not exist',
            'assignments.*.shift_id.required' => 'Shift is required',
            'assignments.*.shift_id.exists' => 'Selected shift does not exist',
            'assignments.*.assignment_date.required' => 'Assignment date is required',
            'assignments.*.assignment_date.after_or_equal' => 'Assignment date cannot be in the past',
            'assignments.*.priority.required' => 'Priority is required',
            'assignments.*.priority.in' => 'Priority must be primary, secondary, or backup',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('assignments')) {
            $assignments = $this->assignments;
            
            foreach ($assignments as &$assignment) {
                if (isset($assignment['priority'])) {
                    $assignment['priority'] = strtolower($assignment['priority']);
                }
            }
            
            $this->merge(['assignments' => $assignments]);
        }
    }

    /**
     * Get the assignment data
     */
    public function getAssignments(): array
    {
        return $this->input('assignments', []);
    }
}
