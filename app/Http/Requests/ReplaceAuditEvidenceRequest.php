<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReplaceAuditEvidenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('evidence')) ?? false;
    }

    public function rules(): array
    {
        return [
            'evidence' => [
                'required',
                'file',
                'mimes:pdf,jpg,jpeg,png,webp,docx,xlsx',
                'max:'.(int) config('filesystems.audit_evidence.max_mb', 25) * 1024,
            ],
        ];
    }
}
