<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AssignAuditPrinciplesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->canManageAssignments() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $auditorRule = Rule::exists('users', 'id')
            ->whereNull('requested_role')
            ->whereNot('role', 'admin');

        return [
            'lead_auditor_id' => ['nullable', 'integer', $auditorRule],
            'team_members_present' => ['required', 'accepted'],
            'team_members' => ['nullable', 'array'],
            'team_members.*' => ['integer', $auditorRule],
            'assignments' => ['required', 'array'],
            'assignments.*.principle' => ['required', 'integer', 'between:1,7'],
            'assignments.*.user_id' => ['nullable', 'integer', $auditorRule],
        ];
    }

    /**
     * Ensure a crafted request cannot submit the same principle more than once.
     *
     * @return array<int, \Closure>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $assignments = $this->input('assignments', []);

                if (! is_array($assignments)) {
                    return;
                }

                $hasPrincipleErrors = collect($validator->errors()->keys())
                    ->contains(fn (string $key): bool => str_starts_with($key, 'assignments.')
                        && str_ends_with($key, '.principle'));

                if ($hasPrincipleErrors) {
                    return;
                }

                $principles = collect($assignments)
                    ->filter(fn ($assignment): bool => is_array($assignment))
                    ->pluck('principle')
                    ->filter()
                    ->map(fn ($principle): int => (int) $principle);

                if ($principles->duplicates()->isNotEmpty()) {
                    $validator->errors()->add('assignments', 'Setiap prinsip hanya boleh memiliki satu penanggung jawab.');
                }

                $teamMembers = $this->input('team_members', []);

                if (! is_array($teamMembers)) {
                    return;
                }

                $selectedTeamIds = collect($teamMembers)
                    ->filter(fn ($userId): bool => is_scalar($userId))
                    ->push($this->input('lead_auditor_id'))
                    ->filter(fn ($userId): bool => is_scalar($userId) && $userId !== '')
                    ->map(fn ($userId): string => (string) $userId);

                foreach ($assignments as $index => $assignment) {
                    if (! is_array($assignment)) {
                        continue;
                    }
                    $userId = $assignment['user_id'] ?? null;

                    if (is_scalar($userId) && $userId !== '' && ! $selectedTeamIds->contains((string) $userId)) {
                        $validator->errors()->add(
                            "assignments.{$index}.user_id",
                            'Penanggung jawab prinsip harus dipilih dari tim auditor proyek.',
                        );
                    }
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'lead_auditor_id.exists' => 'Lead Auditor harus merupakan pengguna dengan peran Auditor aktif.',
            'team_members.*.exists' => 'Anggota tim proyek harus merupakan pengguna dengan peran Auditor aktif.',
            'assignments.*.user_id.exists' => 'Auditor yang ditugaskan pada prinsip harus merupakan pengguna dengan peran Auditor aktif.',
        ];
    }
}
