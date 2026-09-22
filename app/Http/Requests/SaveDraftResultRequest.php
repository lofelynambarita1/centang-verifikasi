<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveDraftResultRequest extends FormRequest
{
    /**
     * Authorization is enforced by the route middleware 'can:update,indicator'
     * and re-checked inside the controller.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'result_text' => ['required', 'string'],
            'lock_version' => ['required', 'integer', 'min:0'],
        ];
    }

    public function expectedLockVersion(): int
    {
        return (int) $this->input('lock_version');
    }
}
