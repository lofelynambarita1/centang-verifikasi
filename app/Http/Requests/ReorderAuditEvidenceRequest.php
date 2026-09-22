<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReorderAuditEvidenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('indicator')) ?? false;
    }

    public function rules(): array
    {
        return [
            'evidence_ids' => ['required', 'array', 'min:1'],
            'evidence_ids.*' => ['required', 'integer', 'distinct'],
        ];
    }
}
