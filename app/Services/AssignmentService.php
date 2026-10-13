<?php

namespace App\Services;

use App\Enums\AuditProjectStatus;
use App\Enums\ProjectRole;
use App\Models\AuditIndicatorAssignment;
use App\Models\AuditProject;
use App\Models\AuditProjectIndicator;
use App\Models\AuditProjectMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Owns every indicator-assignment mutation for an audit project.
 *
 * A single audit indicator always has exactly one active PIC held in
 * audit_indicator_assignments (unique per project + indicator). Every change
 * is recorded in audit_assignment_histories so assignment history is never
 * lost, even when a bulk operation overwrites an existing PIC.
 */
class AssignmentService
{
    /**
     * Assign a single indicator to a PIC, or move it when already assigned.
     */
    public function assign(AuditProject $project, AuditProjectIndicator $indicator, User $pic, User $actor, ?string $reason = null): AuditIndicatorAssignment
    {
        $this->assertAssignableStatus($project);
        $this->assertIndicatorBelongsToProject($project, $indicator);
        $this->ensureProjectMember($project, $pic);

        [$assignment, $action] = DB::transaction(function () use ($project, $indicator, $pic, $actor, $reason): array {
            return $this->persistAssignment($project, $indicator->id, $pic->id, $actor, $reason);
        });

        if ($action === 'created') {
            $this->log($project, $actor, 'assignment.created', sprintf(
                'Indikator "%s" ditugaskan kepada "%s".',
                $indicator->code,
                $pic->name,
            ), ['indicator_id' => $indicator->id, 'pic_user_id' => $pic->id]);
        } elseif ($action === 'reassigned') {
            $this->log($project, $actor, 'assignment.reassigned', sprintf(
                'PIC indikator "%s" diganti menjadi "%s".',
                $indicator->code,
                $pic->name,
            ), ['indicator_id' => $indicator->id, 'pic_user_id' => $pic->id]);
        }

        return $assignment;
    }

    /**
     * Explicitly move an already assigned indicator to another PIC.
     */
    public function reassign(AuditProject $project, AuditProjectIndicator $indicator, User $newPic, User $actor): AuditIndicatorAssignment
    {
        return $this->assign($project, $indicator, $newPic, $actor, 'reassigned');
    }

    /**
     * Manually bulk-assign every descendant indicator of a Principle on the
     * given project snapshot to one PIC. Overwrites existing PICs on purpose.
     *
     * @return array{principle_id: int, total: int, created: int, reassigned: int, unaffected: int}
     */
    public function assignPrinciple(AuditProject $project, int $principleId, User $pic, User $actor): array
    {
        $this->assertAssignableStatus($project);
        $this->ensureProjectMember($project, $pic);

        if (! $project->snapshotPrinciples()->whereKey($principleId)->exists()) {
            throw ValidationException::withMessages([
                'node_id' => 'Prinsip tidak ditemukan pada project ini.',
            ]);
        }

        $indicatorIds = $project->snapshotIndicators()
            ->whereIn('audit_project_section_id', $project->snapshotSections()
                ->where('audit_project_principle_id', $principleId)->pluck('id'))
            ->pluck('id');

        return $this->bulkAssign($project, $indicatorIds->all(), $pic, $actor, $principleId, 'principle');
    }

    /**
     * Manually bulk-assign every descendant indicator of a Section, including
     * indicators living in nested child sections. Overwrites existing PICs on
     * purpose.
     *
     * @return array{section_id: int, total: int, created: int, reassigned: int, unaffected: int}
     */
    public function assignSection(AuditProject $project, int $sectionId, User $pic, User $actor): array
    {
        $this->assertAssignableStatus($project);
        $this->ensureProjectMember($project, $pic);

        $sectionIds = $this->sectionAndDescendantIds($project, $sectionId);
        $indicatorIds = $project->snapshotIndicators()
            ->whereIn('audit_project_section_id', $sectionIds)
            ->pluck('id');

        return $this->bulkAssign($project, $indicatorIds->all(), $pic, $actor, $sectionId, 'section');
    }

    /**
     * Automatically distribute wholly unassigned principles across the
     * project's auditor team. Every descendant indicator receives the
     * principle's PIC.
     * Existing principle assignments, including exceptions, are never touched.
     *
     * @return array{principles: int, indicators: int}
     */
    public function autoAssign(AuditProject $project, User $actor): array
    {
        $this->assertAssignableStatus($project);

        $members = $project->members()
            ->whereIn('project_role', [ProjectRole::LeadAuditor->value, ProjectRole::Auditor->value])
            ->orderBy('id')
            ->pluck('user_id')
            ->map(fn ($id): int => (int) $id)
            ->values();

        if ($members->isEmpty()) {
            throw ValidationException::withMessages([
                'team' => 'Project belum memiliki anggota auditor untuk pembagian otomatis.',
            ]);
        }

        $principles = $project->snapshotPrinciples()->get(['id', 'code']);
        $sections = $project->snapshotSections()->get(['id', 'audit_project_principle_id', 'parent_id']);
        $indicators = $project->snapshotIndicators()->get(['id', 'audit_project_section_id']);
        $assignments = $project->assignments()
            ->get(['audit_project_indicator_id', 'pic_user_id'])
            ->groupBy('audit_project_indicator_id');
        $indicatorIdsByPrinciple = $indicators->groupBy(
            fn (AuditProjectIndicator $indicator): int => (int) ($sections->firstWhere('id', $indicator->audit_project_section_id)?->audit_project_principle_id),
        );
        $eligiblePrinciples = $principles
            ->sortBy(fn (object $principle): array => $this->naturalCodeParts((string) $principle->code))
            ->filter(function (object $principle) use ($indicatorIdsByPrinciple, $assignments): bool {
                $principleIndicators = $indicatorIdsByPrinciple->get($principle->id, collect());

                return $principleIndicators->isNotEmpty()
                    && $principleIndicators->every(fn (AuditProjectIndicator $indicator): bool => ! $assignments->has($indicator->id));
            })
            ->values();

        if ($eligiblePrinciples->isEmpty()) {
            return ['principles' => 0, 'indicators' => 0];
        }

        $assignedIndicators = 0;
        $assignedPrinciples = 0;
        $memberIds = $members->all();

        DB::transaction(function () use ($project, $eligiblePrinciples, $indicatorIdsByPrinciple, $memberIds, $actor, &$assignedIndicators, &$assignedPrinciples): void {
            foreach ($eligiblePrinciples as $index => $principle) {
                $picUserId = $memberIds[$index % count($memberIds)];
                foreach ($indicatorIdsByPrinciple->get($principle->id, collect()) as $indicator) {
                    $this->persistAssignment($project, $indicator->id, $picUserId, $actor, 'auto_assigned');
                    $assignedIndicators++;
                }
                $assignedPrinciples++;
            }
        });

        $this->log($project, $actor, 'assignment.auto_assigned', sprintf(
            '%d prinsip (%d indikator) dibagi otomatis kepada %d auditor.',
            $assignedPrinciples,
            $assignedIndicators,
            $members->count(),
        ), [
            'assigned_principles' => $assignedPrinciples,
            'assigned_count' => $assignedIndicators,
            'pic_user_ids' => $members->all(),
        ]);

        return ['principles' => $assignedPrinciples, 'indicators' => $assignedIndicators];
    }

    /** @return array<int, int|string> */
    private function naturalCodeParts(string $code): array
    {
        return array_map(
            fn (string $part): int|string => ctype_digit($part) ? (int) $part : $part,
            preg_split('/[^0-9]+/', $code, -1, PREG_SPLIT_NO_EMPTY) ?: [$code],
        );
    }

    /**
     * Workload per project member, expressed as the number of indicators each
     * member is currently the PIC of.
     *
     * @return Collection<int, AuditProjectMember>
     */
    public function workloads(AuditProject $project): Collection
    {
        $counts = AuditIndicatorAssignment::query()
            ->where('audit_project_id', $project->id)
            ->selectRaw('pic_user_id, COUNT(*) as total')
            ->groupBy('pic_user_id')
            ->pluck('total', 'pic_user_id');

        return $project->members()->with('user')->get()
            ->map(function ($member) use ($counts): mixed {
                $member->setAttribute('assignments_count', (int) ($counts[$member->user_id] ?? 0));

                return $member;
            });
    }

    /**
     * Persist one assignment row and its history entry, returning the
     * assignment together with what happened ('created', 'reassigned' or
     * 'unchanged'). Must only be called inside a running transaction so bulk
     * operations stay atomic and a created assignment cannot survive without
     * its history row.
     *
     * @return array{0: AuditIndicatorAssignment, 1: string}
     */
    private function persistAssignment(AuditProject $project, int $indicatorId, int $picUserId, User $actor, ?string $reason = null): array
    {
        $existing = AuditIndicatorAssignment::query()
            ->where('audit_project_id', $project->id)
            ->where('audit_project_indicator_id', $indicatorId)
            ->lockForUpdate()
            ->first();

        $fromUserId = $existing?->pic_user_id;

        if ($existing !== null && $existing->pic_user_id === $picUserId) {
            return [$existing, 'unchanged'];
        }

        $assignment = $existing !== null
            ? tap($existing)->update([
                'pic_user_id' => $picUserId,
                'assigned_by' => $actor->id,
                'assigned_at' => now(),
            ])
            : $project->assignments()->create([
                'audit_project_indicator_id' => $indicatorId,
                'pic_user_id' => $picUserId,
                'assigned_by' => $actor->id,
                'assigned_at' => now(),
            ]);

        $this->recordHistory($project, $indicatorId, $fromUserId, $picUserId, $actor->id, $reason);

        return [$assignment, ($fromUserId === null ? 'created' : 'reassigned')];
    }

    /**
     * @param  array<int>  $indicatorIds
     * @return array{total: int, created: int, reassigned: int, unaffected: int}
     */
    private function bulkAssign(AuditProject $project, array $indicatorIds, User $pic, User $actor, int $nodeId, string $nodeType): array
    {
        if ($indicatorIds === []) {
            throw ValidationException::withMessages([
                'node_id' => 'Unit '.$nodeType.' ini tidak memiliki indikator yang dapat ditugaskan.',
            ]);
        }

        $summary = ['total' => count($indicatorIds), 'created' => 0, 'reassigned' => 0, 'unaffected' => 0];

        DB::transaction(function () use ($project, $indicatorIds, $pic, $actor, &$summary): void {
            foreach ($indicatorIds as $indicatorId) {
                [, $action] = $this->persistAssignment($project, $indicatorId, $pic->id, $actor, 'assigned');
                $summary[$action]++;
            }
        });

        $this->log($project, $actor, 'assignment.bulk_created', sprintf(
            '%d indikator ditugaskan sekaligus kepada "%s".',
            $summary['total'],
            $pic->name,
        ), [
            'node_id' => $nodeId,
            'node_type' => $nodeType,
            'total' => $summary['total'],
            'created' => $summary['created'],
            'reassigned' => $summary['reassigned'],
            'pic_user_id' => $pic->id,
        ]);

        return $summary + [$nodeType.'_id' => $nodeId];
    }

    /**
     * @return array<int>
     */
    private function sectionAndDescendantIds(AuditProject $project, int $rootId): array
    {
        $all = $project->snapshotSections()->get(['id', 'parent_id']);

        if ($all->doesntContain('id', $rootId)) {
            throw ValidationException::withMessages([
                'node_id' => 'Kriteria tidak ditemukan pada project ini.',
            ]);
        }

        $byParent = $all->groupBy('parent_id');
        $ids = [$rootId];
        $queue = [$rootId];

        while (($parentId = array_shift($queue)) !== null) {
            foreach ($byParent->get($parentId, collect()) as $child) {
                $ids[] = $child->id;
                $queue[] = $child->id;
            }
        }

        return $ids;
    }

    /**
     * Assignment mutations are only legal while the project is in the
     * "assignment" status. Draft, team onboarding, an in-progress audit and
     * everything after it must not let anyone touch who the PIC is.
     */
    private function assertAssignableStatus(AuditProject $project): void
    {
        if ($project->status !== AuditProjectStatus::Assignment) {
            throw ValidationException::withMessages([
                'status' => 'Penugasan indikator hanya dapat diubah ketika project berstatus "Penugasan Indikator" (assignment).',
            ]);
        }
    }

    private function assertIndicatorBelongsToProject(AuditProject $project, AuditProjectIndicator $indicator): void
    {
        if ($indicator->audit_project_id !== $project->id) {
            throw ValidationException::withMessages([
                'indicator' => 'Indikator tidak termasuk dalam project ini.',
            ]);
        }
    }

    private function ensureProjectMember(AuditProject $project, User $user): void
    {
        if (! $project->members()->where('user_id', $user->id)->exists()) {
            throw ValidationException::withMessages([
                'pic_user_id' => 'User "'.$user->name.'" bukan anggota project ini dan tidak dapat menjadi PIC.',
            ]);
        }
    }

    private function recordHistory(AuditProject $project, int $indicatorId, ?int $fromUserId, int $toUserId, int $changedBy, ?string $reason): void
    {
        $project->assignmentHistories()->create([
            'audit_project_indicator_id' => $indicatorId,
            'from_user_id' => $fromUserId,
            'to_user_id' => $toUserId,
            'changed_by' => $changedBy,
            'reason' => $reason,
            'created_at' => now(),
        ]);
    }

    private function log(AuditProject $project, User $actor, string $activity, string $description, ?array $meta = null): void
    {
        $project->activityLogs()->create([
            'user_id' => $actor->id,
            'activity' => $activity,
            'description' => $description,
            'meta' => $meta,
        ]);
    }
}
