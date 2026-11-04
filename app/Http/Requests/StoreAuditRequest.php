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
            'scope_category' => ['required', Rule::in(['hulu', 'hilir', 'bioenergi'])],
            'scope_detail' => [
                'required_if:scope_category,hulu',
                'prohibited_unless:scope_category,hulu',
                'nullable',
                Rule::in(['hulu_kebun', 'hulu_pks', 'hulu_integrasi']),
            ],
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
            'scope_category.required' => 'Ruang lingkup verifikasi wajib dipilih.',
            'scope_category.in' => 'Ruang lingkup verifikasi yang dipilih tidak valid.',
            'scope_detail.required_if' => 'Sub-kategori Hulu wajib dipilih.',
            'scope_detail.prohibited_unless' => 'Sub-kategori hanya dapat dipilih untuk ruang lingkup Hulu.',
            'scope_detail.in' => 'Sub-kategori Hulu yang dipilih tidak valid.',
            'lead_auditor_id.required' => 'Lead Auditor proyek wajib dipilih.',
            'lead_auditor_id.exists' => 'Lead Auditor harus dipilih dari pengguna dengan peran auditor yang aktif.',
        ];
    }
}
