<?php

namespace App\Http\Requests;

use App\Rules\IsSystemAuditor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReplaceLeadAuditorRequest extends FormRequest
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
            'new_lead_auditor_id' => ['required', 'integer', Rule::exists('users', 'id'), new IsSystemAuditor],
        ];
    }
}
