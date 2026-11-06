<?php

namespace App\Http\Requests;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCertificationProcessRequest extends FormRequest
{
    public function authorize(): bool
    {
        $company = $this->route('company');
        $user = $this->user();

        return $company instanceof Company
            && $user instanceof User
            && $company->canManageCertificationProcess($user);
    }

    public function rules(): array
    {
        $documentTypes = ['file', 'mimes:pdf,doc,docx', 'extensions:pdf,doc,docx', 'max:20480'];

        return [
            'summary_document' => ['nullable', ...$documentTypes],
            'audit_correction_document' => ['nullable', ...$documentTypes],
            'audit_report' => ['nullable', 'string', 'max:30000'],
            'review_panel' => ['nullable', 'string', 'max:30000'],
            'certificate_document' => ['nullable', 'file', 'mimes:pdf', 'extensions:pdf', 'max:20480'],
        ];
    }
}
