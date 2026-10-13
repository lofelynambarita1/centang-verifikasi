<?php

namespace App\Services;

use App\Enums\AuditProgressState;
use App\Enums\AuditResultWorkflowStatus;
use App\Models\AuditProject;
use App\Models\AuditProjectIndicator;
use App\Models\User;
use Illuminate\Support\Collection;

class AuditProgressService
{
    /**
     * @return array{
     *     total: int,
     *     not_started: int,
     *     status_pending: int,
     *     draft_complete: int,
     *     submitted: int,
     *     revision_requested: int,
     *     approved: int,
     *     revision_requested: int,
     *     approved: int,
     *     indicators_with_result: int,
     *     filling_percentage: float,
     *     completeness_percentage: float
     *     completed: int,
     *     completion_percentage: float
     * }
     */
    public function projectSummary(AuditProject $project): array
    {
        return $this->summaryForIndicators($this->indicators($project));
    }

    /**
     * @return Collection<int, array{
     *     user: User,
     *     assigned: int,
     *     not_started: int,
     *     status_pending: int,
     *     draft_complete: int,
     *     submitted: int,
     *     completeness_percentage: float
     * }>
     */
    public function auditorBreakdown(AuditProject $project): Collection
    {
        $indicators = $this->indicators($project);
        $members = $project->members->whereNotNull('user');
        $assignments = $indicators->filter(fn (AuditProjectIndicator $indicator): bool => $indicator->assignment !== null)
            ->groupBy(fn (AuditProjectIndicator $indicator): int => $indicator->assignment->pic_user_id);

        return $members
            ->map(function ($member) use ($assignments): array {
                $summary = $this->summaryForIndicators($assignments->get($member->user_id, collect()));

                return [
                    'user' => $member->user,
                    'assigned' => $summary['total'],
                    'not_started' => $summary['not_started'],
                    'status_pending' => $summary['status_pending'],
                    'draft_complete' => $summary['draft_complete'],
                    'submitted' => $summary['submitted'],
                    'revision_requested' => $summary['revision_requested'],
                    'approved' => $summary['approved'],
                    'completeness_percentage' => $summary['completeness_percentage'],
                ];
            })
            ->values();
    }

    /**
     * @return Collection<int, array{
     *     principle: mixed,
     *     total: int,
     *     not_started: int,
     *     status_pending: int,
     *     draft_complete: int,
     *     submitted: int,
     *     revision_requested: int,
     *     approved: int,
     *     completeness_percentage: float
     * }>
     */
    public function principleBreakdown(AuditProject $project): Collection
    {
        $indicators = $this->indicators($project);
        $principles = $project->snapshotPrinciples;

        return $principles->map(function ($principle) use ($indicators): array {
            $summary = $this->summaryForIndicators(
                $indicators->filter(fn (AuditProjectIndicator $indicator): bool => $indicator->section?->audit_project_principle_id === $principle->id),
            );

            return [
                'principle' => $principle,
                'total' => $summary['total'],
                'not_started' => $summary['not_started'],
                'status_pending' => $summary['status_pending'],
                'draft_complete' => $summary['draft_complete'],
                'submitted' => $summary['submitted'],
                'revision_requested' => $summary['revision_requested'],
                'approved' => $summary['approved'],
                'completeness_percentage' => $summary['completeness_percentage'],
            ];
        });
    }

    /**
     * @return Collection<int, AuditProjectIndicator>
     */
    public function indicatorDetails(AuditProject $project): Collection
    {
        return $this->indicators($project)->map(function (AuditProjectIndicator $indicator): AuditProjectIndicator {
            $indicator->setAttribute('progress_state', $this->stateFor($indicator));

            return $indicator;
        });
    }

    public function reviewQueue(AuditProject $project, ?string $workflowStatus = 'submitted', ?int $auditorId = null): Collection
    {
        return $this->indicators($project)
            ->filter(function (AuditProjectIndicator $indicator) use ($workflowStatus, $auditorId): bool {
                $matchesStatus = $workflowStatus === 'all'
                    || $indicator->result?->workflow_status?->value === $workflowStatus;
                $matchesAuditor = $auditorId === null || $indicator->assignment?->pic_user_id === $auditorId;

                return $matchesStatus && $matchesAuditor;
            })
            ->sortBy(function (AuditProjectIndicator $indicator): array {
                return [
                    $indicator->result?->submitted_at?->timestamp ?? PHP_INT_MAX,
                    $indicator->sort_order,
                    $indicator->id,
                ];
            })
            ->values();
    }

    /**
     * @return array{
     *     total: int,
     *     not_started: int,
     *     status_pending: int,
     *     draft_complete: int,
     *     submitted: int,
     *     revision_requested: int,
     *     approved: int,
     *     indicators_with_result: int,
     *     filling_percentage: float,
     *     completeness_percentage: float,
     *     completed: int,
     *     completion_percentage: float
     * }
     */
    public function userSummary(AuditProject $project, User $user): array
    {
        $indicators = $this->indicators($project)
            ->filter(fn (AuditProjectIndicator $indicator): bool => $indicator->assignment?->pic_user_id === $user->id)
            ->values();

        return $this->summaryForIndicators($indicators);
    }

    public function stateFor(AuditProjectIndicator $indicator): AuditProgressState
    {
        if ($indicator->result === null) {
            return AuditProgressState::NotStarted;
        }

        if ($indicator->result->workflow_status === AuditResultWorkflowStatus::Submitted) {
            return AuditProgressState::Submitted;
        }

        if ($indicator->result->workflow_status === AuditResultWorkflowStatus::RevisionRequested) {
            return AuditProgressState::RevisionRequested;
        }

        if ($indicator->result->workflow_status === AuditResultWorkflowStatus::Approved) {
            return AuditProgressState::Approved;
        }

        return $indicator->result->assessment_status === null
            ? AuditProgressState::StatusPending
            : AuditProgressState::DraftComplete;
    }

    /**
     * @param  Collection<int, AuditProjectIndicator>  $indicators
     * @return array{
     *     total: int,
     *     not_started: int,
     *     status_pending: int,
     *     draft_complete: int,
     *     indicators_with_result: int,
     *     filling_percentage: float,
     *     completeness_percentage: float
     * }
     */
    private function summaryForIndicators(Collection $indicators): array
    {
        $states = $indicators->map(fn (AuditProjectIndicator $indicator): AuditProgressState => $this->stateFor($indicator));
        $total = $states->count();
        $notStarted = $states->filter(fn (AuditProgressState $state): bool => $state === AuditProgressState::NotStarted)->count();
        $statusPending = $states->filter(fn (AuditProgressState $state): bool => $state === AuditProgressState::StatusPending)->count();
        $draftComplete = $states->filter(fn (AuditProgressState $state): bool => $state === AuditProgressState::DraftComplete)->count();
        $submitted = $states->filter(fn (AuditProgressState $state): bool => $state === AuditProgressState::Submitted)->count();
        $revisionRequested = $states->filter(fn (AuditProgressState $state): bool => $state === AuditProgressState::RevisionRequested)->count();
        $approved = $states->filter(fn (AuditProgressState $state): bool => $state === AuditProgressState::Approved)->count();
        $withResult = $total - $notStarted;

        return [
            'total' => $total,
            'not_started' => $notStarted,
            'status_pending' => $statusPending,
            'draft_complete' => $draftComplete,
            'submitted' => $submitted,
            'revision_requested' => $revisionRequested,
            'approved' => $approved,
            'indicators_with_result' => $withResult,
            'filling_percentage' => $this->percentage($withResult, $total),
            'completeness_percentage' => $this->percentage($draftComplete, $total),
            'completed' => $approved,
            'completion_percentage' => $this->percentage($approved, $total),
        ];
    }

    /**
     * @return Collection<int, AuditProjectIndicator>
     */
    private function indicators(AuditProject $project): Collection
    {
        if (! $project->relationLoaded('members')) {
            $project->load('members.user');
        }

        return $project->snapshotIndicators()
            ->with(['section.principle', 'assignment.pic', 'result', 'activeEvidences'])
            ->get();
    }

    private function percentage(int $value, int $total): float
    {
        return $total === 0 ? 0.0 : round(($value / $total) * 100, 2);
    }
}
