<?php

namespace App\Services;

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
        $this->ensureProjectMember($project, $pic);

        $sectionIds = $this->sectionAndDescendantIds($project, $sectionId);
        $indicatorIds = $project->snapshotIndicators()
            ->whereIn('audit_project_section_id', $sectionIds)
            ->pluck('id');

        return $this->bulkAssign($project, $indicatorIds->all(), $pic, $actor, $sectionId, 'section');
    }

    /**
     * Automatically distribute only the UNASSIGNED indicators of the project
     * across the chosen auditors as evenly as possible, based on their current
     * workload. Existing assignments are never touched.
     *
     * @param  array<int>  $picUserIds
     */
    public function autoAssign(AuditProject $project, array $picUserIds, User $actor): int
    {
        $members = collect($picUserIds)->map(fn ($id): int => (int) $id)->unique()->values();

        if ($members->isEmpty()) {
            throw ValidationException::withMessages([
                'pic_user_ids' => 'Pilih minimal satu auditor untuk pembagian otomatis.',
            ]);
        }

        $members->each(function (int $userId) use ($project): void {
            $this->ensureProjectMember($project, User::findOrFail($userId));
        });

        $unassigned = $project->snapshotIndicators()
            ->whereDoesntHave('assignment')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->pluck('id');

        if ($unassigned->isEmpty()) {
            return 0;
        }

        $workload = AuditIndicatorAssignment::query()
            ->where('audit_project_id', $project->id)
            ->whereIn('pic_user_id', $members)
            ->selectRaw('pic_user_id, COUNT(*) as total')
            ->groupBy('pic_user_id')
            ->pluck('total', 'pic_user_id');

        $assigned = 0;

        DB::transaction(function () use ($project, $unassigned, $members, $workload, $actor, &$assigned): void {
            foreach ($unassigned as $indicatorId) {
                $picUserId = $this->nextLowestWorkload($members->all(), $workload);
                $this->persistAssignment($project, $indicatorId, $picUserId, $actor, 'auto_assigned');
                $workload[$picUserId] = (int) $workload->get($picUserId, 0) + 1;
                $assigned++;
            }
        });

        $this->log($project, $actor, 'assignment.auto_assigned', sprintf(
            '%d indikator dibagi otomatis kepada %d auditor.',
            $assigned,
            $members->count(),
        ), [
            'assigned_count' => $assigned,
            'pic_user_ids' => $members->all(),
        ]);

        return $assigned;
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
     * @param  array<int>  $memberIds
     * @param  \Illuminate\Support\Collection<int, int>  $workload
     */
    private function nextLowestWorkload(array $memberIds, $workload): int
    {
        $candidates = array_values($memberIds);
        usort($candidates, function (int $a, int $b) use ($workload): int {
            $loadA = (int) $workload->get($a, 0);
            $loadB = (int) $workload->get($b, 0);

            return $loadA <=> $loadB ?: $a <=> $b;
        });

        return $candidates[0];
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
