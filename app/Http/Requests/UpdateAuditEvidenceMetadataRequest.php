<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAuditEvidenceMetadataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('evidence')) ?? false;
    }

    public function rules(): array
    {
        return [
            'caption' => ['nullable', 'string', 'max:500'],
            'is_report_included' => ['required', 'boolean'],
        ];
    }
}
