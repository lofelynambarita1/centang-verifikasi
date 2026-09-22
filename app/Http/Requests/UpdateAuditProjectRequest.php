<?php

namespace App\Http\Requests;

use App\Enums\AuditProjectStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAuditProjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'template_id' => ['required', 'integer', Rule::exists('audit_templates', 'id')->where('is_active', 1)],
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'name' => ['required', 'string', 'max:255'],
            'audit_type' => ['required', 'string', 'max:255'],
            'audit_start_date' => ['required', 'date'],
            'audit_end_date' => ['required', 'date', 'after_or_equal:audit_start_date'],
            'land_area' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::enum(AuditProjectStatus::class)],
        ];
    }
}
