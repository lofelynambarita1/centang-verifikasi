<?php

namespace App\Services;

use App\Enums\AuditProjectStatus;
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

            if ($result->workflow_status !== AuditResultWorkflowStatus::Draft) {
                throw ValidationException::withMessages(['workflow_status' => 'Hasil Audit sudah dikirim untuk review.']);
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

            $project->activityLogs()->create([
                'user_id' => $actor->id,
                'activity' => 'audit_result.submitted',
                'description' => 'Hasil Audit indikator "'.$indicator->code.'" dikirim untuk review.',
                'meta' => ['indicator_id' => $indicator->id, 'result_id' => $result->id],
            ]);

            return $result->refresh();
        });
    }
}
