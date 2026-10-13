<?php

namespace App\Services;

use App\Enums\ProjectRole;
use App\Models\AuditProject;
use App\Models\AuditProjectIndicator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class AuditReportDataService
{
    /**
     * Assemble a read-only report projection from the project's snapshots.
     *
     * @return array{
     *     project: AuditProject,
     *     company: mixed,
     *     assessment_summary: ?string,
     *     lead_auditor: mixed,
     *     auditors: Collection,
     *     auditor_team: Collection,
     *     audit_date: string,
     *     principles: Collection
     * }
     */
    public function assemble(AuditProject $project): array
    {
        $project->load([
            'company',
            'leadMember.user',
            'members.user',
            'snapshotPrinciples.sections.indicators.parameters',
            'snapshotPrinciples.sections.indicators.result',
            'snapshotPrinciples.sections.indicators.activeEvidences.uploader',
        ]);

        $principles = $project->snapshotPrinciples->map(function ($principle): array {
            return [
                'principle' => $principle,
                'sections' => $principle->sections->map(function ($section): array {
                    return [
                        'section' => $section,
                        'indicators' => $section->indicators->map(
                            fn (AuditProjectIndicator $indicator): array => $this->indicator($indicator),
                        ),
                    ];
                }),
            ];
        });

        $auditors = $project->members
            ->filter(fn ($member): bool => in_array(
                $member->project_role,
                [ProjectRole::LeadAuditor, ProjectRole::Auditor],
                true,
            ))
            ->filter(fn ($member): bool => $member->user !== null)
            ->sort(function ($left, $right): int {
                $roleOrder = ($left->project_role === ProjectRole::LeadAuditor ? 0 : 1)
                    <=> ($right->project_role === ProjectRole::LeadAuditor ? 0 : 1);

                if ($roleOrder !== 0) {
                    return $roleOrder;
                }

                $createdAtOrder = ($left->created_at?->getTimestamp() ?? 0)
                    <=> ($right->created_at?->getTimestamp() ?? 0);

                return $createdAtOrder !== 0
                    ? $createdAtOrder
                    : $left->id <=> $right->id;
            })
            ->unique('user_id')
            ->values()
            ->map(fn ($member) => $member->user);

        return [
            'project' => $project,
            'company' => $project->company,
            'assessment_summary' => $project->assessment_summary,
            'lead_auditor' => $project->leadMember?->user,
            'auditors' => $auditors,
            'auditor_team' => $project->members->filter(fn ($member): bool => $member->user !== null)->values(),
            'audit_date' => $this->auditPeriod($project),
            'principles' => $principles,
        ];
    }

    private function auditPeriod(AuditProject $project): string
    {
        $start = $project->audit_start_date?->format('d M Y');
        $end = $project->audit_end_date?->format('d M Y');

        return match (true) {
            $start !== null && $end !== null => $start.' - '.$end,
            $start !== null => $start,
            $end !== null => $end,
            default => '-',
        };
    }

    /**
     * @return array{indicator: AuditProjectIndicator, parameters: Collection, evidences: Collection}
     */
    private function indicator(AuditProjectIndicator $indicator): array
    {
        $evidences = $indicator->activeEvidences
            ->where('is_report_included', true)
            ->sortBy([
                ['sort_order', 'asc'],
                ['id', 'asc'],
            ])
            ->values()
            ->map(function ($evidence) {
                if (in_array(strtolower((string) $evidence->extension), ['jpg', 'jpeg', 'png', 'webp'], true)) {
                    $disk = Storage::disk($evidence->storage_disk);
                    $evidence->setAttribute('preview_data', $disk->exists($evidence->storage_path)
                        ? 'data:'.$evidence->mime_type.';base64,'.base64_encode($disk->get($evidence->storage_path))
                        : null);
                }

                return $evidence;
            });

        return [
            'indicator' => $indicator,
            'parameters' => $indicator->parameters,
            'evidences' => $evidences,
        ];
    }
}
