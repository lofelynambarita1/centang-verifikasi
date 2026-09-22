<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AutoAssignmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'pic_user_ids' => ['required', 'array', 'min:1'],
            'pic_user_ids.*' => ['integer', 'exists:users,id'],
        ];
    }
}
