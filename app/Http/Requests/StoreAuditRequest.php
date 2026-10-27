<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAuditRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'scope' => ['nullable', 'string', 'max:255'],
            'lead_auditor_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')
                    ->where('role', 'auditor')
                    ->whereNull('requested_role'),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'company_id.required' => 'Perusahaan / unit kebun wajib dipilih.',
            'company_id.exists' => 'Perusahaan yang dipilih tidak valid.',
            'start_date.required' => 'Tanggal mulai audit wajib diisi.',
            'end_date.required' => 'Tanggal selesai audit wajib diisi.',
            'end_date.after_or_equal' => 'Tanggal selesai audit tidak boleh sebelum tanggal mulai audit.',
            'lead_auditor_id.required' => 'Lead Auditor proyek wajib dipilih.',
            'lead_auditor_id.exists' => 'Lead Auditor harus dipilih dari pengguna dengan peran auditor yang aktif.',
        ];
    }
}
