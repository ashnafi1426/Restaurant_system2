<?php

namespace App\Http\Requests\Manager;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignFloorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && (
            in_array(auth()->user()->role, ['manager', 'admin', 'superadmin', 'super_admin'])
            || auth()->user()->hasRole('manager')
            || auth()->user()->hasRole('admin')
        );
    }

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
                function ($attribute, $value, $fail) {
                    $existsInFloors = \Illuminate\Support\Facades\Schema::hasTable('floors')
                        && \Illuminate\Support\Facades\DB::table('floors')->where('id', $value)->exists();
                    $existsInHotelFloors = \Illuminate\Support\Facades\Schema::hasTable('hotel_floors') 
                        && \Illuminate\Support\Facades\DB::table('hotel_floors')->where('id', $value)->exists();
                    if (!$existsInFloors && !$existsInHotelFloors) {
                        $fail('Selected floor does not exist.');
                    }
                }
            ],
            'assignments.*.shift_id' => [
                'nullable',
                'uuid',
            ],
            'assignments.*.assignment_date' => [
                'nullable',
                'date',
            ],
            'assignments.*.status' => [
                'nullable',
                Rule::in(['active', 'inactive']),
            ],
            'assignments.*.priority' => [
                'nullable',
                Rule::in(['primary', 'secondary', 'backup']),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'assignments.required' => 'At least one assignment is required',
            'assignments.min' => 'At least one assignment must be provided',
            'assignments.*.waiter_id.required' => 'Waiter is required',
            'assignments.*.waiter_id.exists' => 'Selected waiter does not exist',
            'assignments.*.floor_id.required' => 'Floor is required',
            'assignments.*.floor_id.exists' => 'Selected floor does not exist',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('assignments')) {
            $assignments = $this->assignments;
            
            foreach ($assignments as &$assignment) {
                if (empty($assignment['priority'])) {
                    $assignment['priority'] = 'primary';
                } else {
                    $assignment['priority'] = strtolower($assignment['priority']);
                }

                if (empty($assignment['status'])) {
                    $assignment['status'] = 'active';
                } else {
                    $assignment['status'] = strtolower($assignment['status']);
                }

                if (empty($assignment['assignment_date'])) {
                    $assignment['assignment_date'] = now()->format('Y-m-d');
                }

                if (empty($assignment['shift_id'])) {
                    $assignment['shift_id'] = null;
                }
            }
            
            $this->merge(['assignments' => $assignments]);
        }
    }

    public function getAssignments(): array
    {
        return $this->input('assignments', []);
    }
}
