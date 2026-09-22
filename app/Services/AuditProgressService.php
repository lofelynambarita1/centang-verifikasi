<?php

namespace App\Services;

use App\Enums\AuditProgressState;
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
     *     indicators_with_result: int,
     *     filling_percentage: float,
     *     completeness_percentage: float
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

    /**
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
        $withResult = $total - $notStarted;

        return [
            'total' => $total,
            'not_started' => $notStarted,
            'status_pending' => $statusPending,
            'draft_complete' => $draftComplete,
            'indicators_with_result' => $withResult,
            'filling_percentage' => $this->percentage($withResult, $total),
            'completeness_percentage' => $this->percentage($draftComplete, $total),
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
            ->with(['section.principle', 'assignment.pic', 'result'])
            ->get();
    }

    private function percentage(int $value, int $total): float
    {
        return $total === 0 ? 0.0 : round(($value / $total) * 100, 2);
    }
}
