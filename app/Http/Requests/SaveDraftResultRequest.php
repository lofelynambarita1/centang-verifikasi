<?php

namespace App\Http\Requests;

use App\Enums\AuditAssessmentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'assessment_status' => ['nullable', Rule::enum(AuditAssessmentStatus::class)],
            'lock_version' => ['required', 'integer', 'min:0'],
        ];
    }

    public function expectedLockVersion(): int
    {
        return (int) $this->input('lock_version');
    }

    public function assessmentStatus(): ?AuditAssessmentStatus
    {
        return $this->enum('assessment_status', AuditAssessmentStatus::class);
    }
}
