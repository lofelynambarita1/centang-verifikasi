<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitAuditResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('indicator')) ?? false;
    }

    public function rules(): array
    {
        return [
            'lock_version' => ['required', 'integer', 'min:0'],
        ];
    }

    public function expectedLockVersion(): int
    {
        return (int) $this->input('lock_version');
    }
}
