<?php

namespace App\Services;

use App\Enums\AuditProjectStatus;
use App\Enums\AuditResultReviewAction;
use App\Enums\AuditResultWorkflowStatus;
use App\Models\AuditIndicatorResult;
use App\Models\AuditProject;
use App\Models\AuditProjectIndicator;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AuditResultWorkflowService
{
    public function submit(
        AuditProject $project,
        AuditProjectIndicator $indicator,
        User $actor,
        int $expectedLockVersion,
    ): AuditIndicatorResult {
        if ($project->status !== AuditProjectStatus::InProgress) {
            throw ValidationException::withMessages(['status' => 'Hasil Audit hanya dapat dikirim ketika project berstatus "Berjalan" (in_progress).']);
        }

        if ($indicator->assignment?->pic_user_id !== $actor->id) {
            throw ValidationException::withMessages(['indicator' => 'Hanya PIC dari indikator ini yang dapat mengirim Hasil Audit.']);
        }

        return DB::transaction(function () use ($project, $indicator, $actor, $expectedLockVersion): AuditIndicatorResult {
            $result = AuditIndicatorResult::query()
                ->where('audit_project_id', $project->id)
                ->where('audit_project_indicator_id', $indicator->id)
                ->lockForUpdate()
                ->first();

            if ($result === null) {
                throw ValidationException::withMessages(['result' => 'Hasil Audit belum tersedia.']);
            }

            if ($expectedLockVersion !== $result->lock_version) {
                throw ValidationException::withMessages([
                    'lock_version' => 'Hasil Audit berubah sejak halaman ini dibuka. Muat ulang sebelum mengirim untuk review.',
                ]);
            }

            if (! in_array($result->workflow_status, [
                AuditResultWorkflowStatus::Draft,
                AuditResultWorkflowStatus::RevisionRequested,
            ], true)) {
                throw ValidationException::withMessages(['workflow_status' => 'Hasil Audit belum dapat dikirim untuk review.']);
            }

            if (trim((string) $result->result_text) === '') {
                throw ValidationException::withMessages(['result_text' => 'Hasil Audit tidak boleh kosong.']);
            }

            if ($result->assessment_status === null) {
                throw ValidationException::withMessages(['assessment_status' => 'Pilih status penilaian sebelum mengirim untuk review.']);
            }

            $result->update([
                'workflow_status' => AuditResultWorkflowStatus::Submitted,
                'submitted_at' => now(),
                'submitted_by' => $actor->id,
            ]);

            $this->recordReviewEvent($project, $indicator, $result, AuditResultReviewAction::Submitted, null, $actor);

            $project->activityLogs()->create([
                'user_id' => $actor->id,
                'activity' => 'audit_result.submitted',
                'description' => 'Hasil Audit indikator "'.$indicator->code.'" dikirim untuk review.',
                'meta' => ['indicator_id' => $indicator->id, 'result_id' => $result->id],
            ]);

            return $result->refresh();
        });
    }

    public function requestRevision(
        AuditProject $project,
        AuditProjectIndicator $indicator,
        User $actor,
        int $expectedLockVersion,
        string $note,
    ): AuditIndicatorResult {
        return $this->review($project, $indicator, $actor, $expectedLockVersion, AuditResultWorkflowStatus::RevisionRequested, AuditResultReviewAction::RevisionRequested, $note);
    }

    public function approve(
        AuditProject $project,
        AuditProjectIndicator $indicator,
        User $actor,
        int $expectedLockVersion,
        ?string $note,
    ): AuditIndicatorResult {
        return $this->review($project, $indicator, $actor, $expectedLockVersion, AuditResultWorkflowStatus::Approved, AuditResultReviewAction::Approved, $note);
    }

    public function finalizeOwnIndicator(
        AuditProject $project,
        AuditProjectIndicator $indicator,
        User $actor,
        int $expectedLockVersion,
        ?string $note,
    ): AuditIndicatorResult {
        if ($project->status !== AuditProjectStatus::InProgress) {
            throw ValidationException::withMessages(['status' => 'Finalisasi hanya dapat dilakukan ketika project berstatus "Berjalan" (in_progress).']);
        }

        if (! $project->userIsLead($actor)) {
            throw ValidationException::withMessages(['actor' => 'Hanya Lead Auditor project yang dapat finalisasi indikator miliknya.']);
        }

        if ($indicator->assignment?->pic_user_id !== $actor->id) {
            throw ValidationException::withMessages(['actor' => 'Finalisasi hanya dapat dilakukan untuk indikator yang Anda PIC-kan.']);
        }

        return DB::transaction(function () use ($project, $indicator, $actor, $expectedLockVersion, $note): AuditIndicatorResult {
            $result = AuditIndicatorResult::query()
                ->where('audit_project_id', $project->id)
                ->where('audit_project_indicator_id', $indicator->id)
                ->lockForUpdate()
                ->first();

            if ($result === null) {
                throw ValidationException::withMessages(['result' => 'Hasil Audit belum tersedia.']);
            }

            if (! in_array($result->workflow_status, [
                AuditResultWorkflowStatus::Draft,
                AuditResultWorkflowStatus::RevisionRequested,
            ], true)) {
                throw ValidationException::withMessages(['workflow_status' => 'Indikator milik Lead Auditor hanya dapat diselesaikan dari draft atau revisi.']);
            }

            if ($expectedLockVersion !== $result->lock_version) {
                throw ValidationException::withMessages([
                    'lock_version' => 'Hasil Audit berubah sejak halaman ini dibuka. Muat ulang sebelum finalisasi.',
                ]);
            }

            if (trim((string) $result->result_text) === '') {
                throw ValidationException::withMessages(['result_text' => 'Hasil Audit tidak boleh kosong.']);
            }

            if ($result->assessment_status === null) {
                throw ValidationException::withMessages(['assessment_status' => 'Pilih status penilaian sebelum finalisasi.']);
            }

            $attributes = [
                'workflow_status' => AuditResultWorkflowStatus::Approved,
                'approved_at' => now(),
                'approved_by' => $actor->id,
            ];
            $result->update($attributes);

            $this->recordReviewEvent($project, $indicator, $result, AuditResultReviewAction::Approved, $note, $actor);
            $project->activityLogs()->create([
                'user_id' => $actor->id,
                'activity' => 'audit_result.approved',
                'description' => 'Lead Auditor menyelesaikan audit indikator "'.$indicator->code.'" secara langsung.',
                'meta' => ['indicator_id' => $indicator->id, 'result_id' => $result->id],
            ]);

            return $result->refresh();
        });
    }

    private function review(
        AuditProject $project,
        AuditProjectIndicator $indicator,
        User $actor,
        int $expectedLockVersion,
        AuditResultWorkflowStatus $status,
        AuditResultReviewAction $action,
        ?string $note,
    ): AuditIndicatorResult {
        if ($project->status !== AuditProjectStatus::InProgress) {
            throw ValidationException::withMessages(['status' => 'Review hanya dapat dilakukan ketika project berstatus "Berjalan" (in_progress).']);
        }

        if (! $project->userIsLead($actor)) {
            throw ValidationException::withMessages(['actor' => 'Hanya Lead Auditor project yang dapat memberikan keputusan review.']);
        }

        if ($indicator->assignment?->pic_user_id === $actor->id) {
            throw ValidationException::withMessages(['actor' => 'Lead Auditor tidak dapat mereview indikator yang dikerjakannya sendiri.']);
        }

        return DB::transaction(function () use ($project, $indicator, $actor, $expectedLockVersion, $status, $action, $note): AuditIndicatorResult {
            $result = AuditIndicatorResult::query()
                ->where('audit_project_id', $project->id)
                ->where('audit_project_indicator_id', $indicator->id)
                ->lockForUpdate()
                ->first();

            if ($result === null || $result->workflow_status !== AuditResultWorkflowStatus::Submitted) {
                throw ValidationException::withMessages(['workflow_status' => 'Keputusan review hanya dapat diberikan pada hasil yang submitted.']);
            }

            if ($expectedLockVersion !== $result->lock_version) {
                throw ValidationException::withMessages([
                    'lock_version' => 'Hasil Audit berubah sejak halaman review dibuka. Muat ulang sebelum memberikan keputusan.',
                ]);
            }

            $attributes = ['workflow_status' => $status];
            if ($status === AuditResultWorkflowStatus::Approved) {
                $attributes['approved_at'] = now();
                $attributes['approved_by'] = $actor->id;
            }
            $result->update($attributes);

            $this->recordReviewEvent($project, $indicator, $result, $action, $note, $actor);
            $activity = 'audit_result.'.$action->value;
            $project->activityLogs()->create([
                'user_id' => $actor->id,
                'activity' => $activity,
                'description' => 'Keputusan review '.$action->value.' untuk indikator "'.$indicator->code.'".',
                'meta' => ['indicator_id' => $indicator->id, 'result_id' => $result->id],
            ]);

            return $result->refresh();
        });
    }

    private function recordReviewEvent(
        AuditProject $project,
        AuditProjectIndicator $indicator,
        AuditIndicatorResult $result,
        AuditResultReviewAction $action,
        ?string $note,
        User $actor,
    ): void {
        $project->resultReviewEvents()->create([
            'audit_project_indicator_id' => $indicator->id,
            'audit_indicator_result_id' => $result->id,
            'action' => $action,
            'note' => $note,
            'actor_id' => $actor->id,
            'content_lock_version' => $result->lock_version,
            'audit_indicator_result_version_id' => $result->versions()->latest('id')->value('id'),
            'created_at' => now(),
        ]);
    }
}
