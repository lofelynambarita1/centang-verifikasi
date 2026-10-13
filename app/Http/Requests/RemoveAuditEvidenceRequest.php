<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RemoveAuditEvidenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('evidence')) ?? false;
    }

    public function rules(): array
    {
        return [
            'removal_reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
