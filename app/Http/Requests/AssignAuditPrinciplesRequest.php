<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignAuditPrinciplesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->canManageAssignments() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'assignments' => ['required', 'array'],
            'assignments.*.principle' => ['required', 'integer', 'between:1,7'],
            'assignments.*.users' => ['nullable', 'array'],
            'assignments.*.users.*' => ['integer', Rule::exists('users', 'id')->whereNull('requested_role')],
        ];
    }
}
