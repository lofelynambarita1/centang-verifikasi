<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAuditEditorImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('indicator')) ?? false;
    }

    public function rules(): array
    {
        return [
            'evidence' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,webp',
                'max:'.(int) config('filesystems.audit_evidence.max_mb', 25) * 1024,
            ],
            'caption' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'evidence.mimes' => 'Untuk saat ini, hanya file gambar yang dapat disisipkan langsung ke Hasil Audit.',
        ];
    }
}
