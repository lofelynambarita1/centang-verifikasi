<?php

namespace App\Http\Requests;

use App\Models\AuditProject;
use App\Rules\IsSystemAuditor;
use App\Rules\ProjectMemberNotDuplicate;
use Illuminate\Foundation\Http\FormRequest;

class AddProjectMemberRequest extends FormRequest
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
        $project = $this->route('auditProject');

        return [
            'user_id' => [
                'required', 'integer', 'exists:users,id',
                new IsSystemAuditor,
                new ProjectMemberNotDuplicate($project instanceof AuditProject ? $project->id : (int) $project),
            ],
        ];
    }
}
