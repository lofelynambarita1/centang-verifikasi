<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FinalizeOwnIndicatorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('finalize', $this->route('indicator')) ?? false;
    }

    public function rules(): array
    {
        return [
            'lock_version' => ['required', 'integer', 'min:0'],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function expectedLockVersion(): int
    {
        return (int) $this->input('lock_version');
    }
}
