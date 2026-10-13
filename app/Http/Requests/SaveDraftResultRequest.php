<?php

namespace App\Http\Requests;

use App\Enums\AuditAssessmentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveDraftResultRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('result_content'))) {
            $decoded = json_decode((string) $this->input('result_content'), true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $this->merge(['result_content' => $decoded]);
            }
        }
    }

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
            'result_text' => ['required_without_all:result_blocks,result_content', 'nullable', 'string'],
            'result_content' => ['nullable', 'array'],
            'result_content.ops' => ['nullable', 'array'],
            'result_blocks' => ['nullable', 'array'],
            'result_blocks.*.type' => ['nullable', 'string', 'in:list_item'],
            'result_blocks.*.level' => ['required_with:result_blocks', 'integer', 'min:1', 'max:3'],
            'result_blocks.*.text' => ['required_with:result_blocks', 'string'],
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

    public function hasStructuredBlocks(): bool
    {
        return $this->has('result_blocks');
    }

    public function hasResultContent(): bool
    {
        return $this->has('result_content');
    }

    /**
     * @return array<string, mixed>
     */
    public function resultContent(): array
    {
        $content = $this->input('result_content', []);

        return is_array($content) ? $content : [];
    }

    /**
     * @return array<int, array{type?: string, level?: int|string, text?: string}>
     */
    public function structuredBlocks(): array
    {
        return $this->validated('result_blocks', []);
    }
}
