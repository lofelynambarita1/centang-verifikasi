<?php

namespace App\Services;

use App\Enums\AuditProjectStatus;
use App\Models\AuditIndicatorResult;
use App\Models\AuditProject;
use App\Models\AuditProjectIndicator;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Owns every "Hasil Audit" draft mutation for a project indicator.
 *
 * Each indicator has at most one active result row (unique per project +
 * indicator). Every genuinely different save appends a row to the version
 * history and bumps lock_version, so concurrent editors can never silently
 * overwrite each other.
 */
class AuditResultService
{
    /**
     * Create or update the draft Hasil Audit of an indicator.
     *
     * @param  string  $resultText  content to persist (after trimming)
     * @param  int  $expectedLockVersion  client's lock_version; new results
     *                                    require zero and existing results
     *                                    must match exactly
     */
    public function saveDraft(AuditProject $project, AuditProjectIndicator $indicator, User $actor, string $resultText, int $expectedLockVersion): AuditIndicatorResult
    {
        $this->assertIndicatorBelongsToProject($project, $indicator);
        $this->assertProjectInProgress($project);
        $this->assertActorIsPic($project, $indicator, $actor);

        $resultText = trim($resultText);

        if ($resultText === '') {
            throw ValidationException::withMessages([
                'result_text' => 'Hasil Audit tidak boleh kosong.',
            ]);
        }

        return DB::transaction(function () use ($project, $indicator, $actor, $resultText, $expectedLockVersion): AuditIndicatorResult {
            $result = AuditIndicatorResult::query()
                ->where('audit_project_id', $project->id)
                ->where('audit_project_indicator_id', $indicator->id)
                ->lockForUpdate()
                ->first();

            if ($result === null) {
                if ($expectedLockVersion !== 0) {
                    $this->throwLockConflict();
                }

                return $this->createResult($project, $indicator, $actor, $resultText);
            }

            if ($expectedLockVersion !== $result->lock_version) {
                $this->throwLockConflict();
            }

            if ($result->result_text === $resultText) {
                return $result;
            }

            return $this->persistUpdatedResult($project, $indicator, $actor, $result, $resultText);
        });
    }

    private function createResult(AuditProject $project, AuditProjectIndicator $indicator, User $actor, string $resultText): AuditIndicatorResult
    {
        $result = $project->results()->create([
            'audit_project_indicator_id' => $indicator->id,
            'result_text' => $resultText,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
            'lock_version' => 1,
        ]);

        $this->recordVersion($result, 1, $resultText, $actor, $project, $indicator);
        $this->log($project, $actor, 'audit_result.created', sprintf(
            'Draft Hasil Audit indikator "%s" dibuat.',
            $indicator->code,
        ), [
            'indicator_id' => $indicator->id,
            'version_number' => 1,
        ]);

        return $result;
    }

    private function persistUpdatedResult(AuditProject $project, AuditProjectIndicator $indicator, User $actor, AuditIndicatorResult $result, string $resultText): AuditIndicatorResult
    {
        $nextVersion = (int) $result->versions()->max('version_number') + 1;

        $result->update([
            'result_text' => $resultText,
            'updated_by' => $actor->id,
            'lock_version' => $result->lock_version + 1,
        ]);

        $this->recordVersion($result, $nextVersion, $resultText, $actor, $project, $indicator);
        $this->log($project, $actor, 'audit_result.updated', sprintf(
            'Draft Hasil Audit indikator "%s" diperbarui.',
            $indicator->code,
        ), [
            'indicator_id' => $indicator->id,
            'version_number' => $nextVersion,
        ]);

        return $result;
    }

    private function recordVersion(AuditIndicatorResult $result, int $versionNumber, string $content, User $actor, AuditProject $project, AuditProjectIndicator $indicator): void
    {
        $result->versions()->create([
            'audit_project_id' => $project->id,
            'audit_project_indicator_id' => $indicator->id,
            'version_number' => $versionNumber,
            'result_text' => $content,
            'changed_by' => $actor->id,
            'created_at' => now(),
        ]);
    }

    private function assertIndicatorBelongsToProject(AuditProject $project, AuditProjectIndicator $indicator): void
    {
        if ($indicator->audit_project_id !== $project->id) {
            throw ValidationException::withMessages([
                'indicator' => 'Indikator tidak termasuk dalam project ini.',
            ]);
        }
    }

    private function assertProjectInProgress(AuditProject $project): void
    {
        if ($project->status !== AuditProjectStatus::InProgress) {
            throw ValidationException::withMessages([
                'status' => 'Hasil Audit hanya dapat disimpan ketika project berstatus "Berjalan" (in_progress).',
            ]);
        }
    }

    private function assertActorIsPic(AuditProject $project, AuditProjectIndicator $indicator, User $actor): void
    {
        $isPic = $project->assignments()
            ->where('audit_project_indicator_id', $indicator->id)
            ->where('pic_user_id', $actor->id)
            ->exists();

        if (! $isPic) {
            throw ValidationException::withMessages([
                'indicator' => 'Hanya PIC dari indikator ini yang dapat menyimpan Hasil Audit.',
            ]);
        }
    }

    private function throwLockConflict(): never
    {
        throw ValidationException::withMessages([
            'lock_version' => 'Hasil Audit telah berubah sejak halaman ini dibuka. Muat ulang halaman sebelum menyimpan kembali.',
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
