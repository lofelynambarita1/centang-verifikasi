<?php

namespace App\Services;

use App\Models\AuditIndicatorEvidence;
use App\Models\AuditProject;
use App\Models\AuditProjectIndicator;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class AuditEvidenceService
{
    public function store(
        AuditProject $project,
        AuditProjectIndicator $indicator,
        User $actor,
        UploadedFile $file,
    ): AuditIndicatorEvidence {
        $disk = (string) config('filesystems.audit_evidence.disk', 'audit_evidence');
        $extension = $file->extension() ?: null;
        $filename = (string) Str::uuid().($extension ? '.'.$extension : '');
        $path = 'project-'.$project->id.'/indicator-'.$indicator->id.'/'.$filename;
        $stored = false;

        try {
            if (! Storage::disk($disk)->putFileAs(dirname($path), $file, basename($path))) {
                throw new \RuntimeException('Evidence storage failed.');
            }

            $stored = true;
            $evidence = DB::transaction(function () use ($project, $indicator, $actor, $file, $disk, $path, $extension): AuditIndicatorEvidence {
                $sortOrder = ((int) $indicator->evidences()->lockForUpdate()->max('sort_order')) + 10;
                $evidence = $indicator->evidences()->create([
                    'audit_project_id' => $project->id,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                    'extension' => $extension,
                    'size_bytes' => $file->getSize(),
                    'sha256' => hash_file('sha256', $file->getRealPath()),
                    'storage_disk' => $disk,
                    'storage_path' => $path,
                    'uploaded_by' => $actor->id,
                    'sort_order' => $sortOrder,
                ]);

                $project->activityLogs()->create([
                    'user_id' => $actor->id,
                    'activity' => 'audit_evidence.uploaded',
                    'description' => 'Evidence diunggah untuk '.$indicator->code.'.',
                    'meta' => [
                        'evidence_id' => $evidence->id,
                        'indicator_id' => $indicator->id,
                        'original_name' => $evidence->original_name,
                        'mime_type' => $evidence->mime_type,
                        'size_bytes' => $evidence->size_bytes,
                        'sha256' => $evidence->sha256,
                    ],
                ]);

                return $evidence;
            });

            return $evidence;
        } catch (Throwable $exception) {
            if ($stored) {
                Storage::disk($disk)->delete($path);
            }

            throw $exception;
        }
    }

    public function updateMetadata(
        AuditProject $project,
        AuditProjectIndicator $indicator,
        AuditIndicatorEvidence $evidence,
        User $actor,
        ?string $caption,
        bool $isReportIncluded,
    ): AuditIndicatorEvidence {
        $before = [
            'caption' => $evidence->caption,
            'is_report_included' => (bool) $evidence->is_report_included,
        ];
        $after = [
            'caption' => $caption,
            'is_report_included' => $isReportIncluded,
        ];

        if ($before === $after) {
            return $evidence;
        }

        return DB::transaction(function () use ($project, $indicator, $evidence, $actor, $before, $after): AuditIndicatorEvidence {
            $evidence->update($after);
            $project->activityLogs()->create([
                'user_id' => $actor->id,
                'activity' => 'audit_evidence.metadata_updated',
                'description' => 'Metadata evidence diperbarui untuk '.$indicator->code.'.',
                'meta' => [
                    'evidence_id' => $evidence->id,
                    'indicator_id' => $indicator->id,
                    'before' => $before,
                    'after' => $after,
                ],
            ]);

            return $evidence->refresh();
        });
    }

    /**
     * @param  array<int, int>  $evidenceIds
     */
    public function reorder(
        AuditProject $project,
        AuditProjectIndicator $indicator,
        User $actor,
        array $evidenceIds,
    ): void {
        DB::transaction(function () use ($project, $indicator, $actor, $evidenceIds): void {
            $evidences = $indicator->evidences()->lockForUpdate()->get()->keyBy('id');

            if ($evidences->keys()->sort()->values()->all() !== collect($evidenceIds)->sort()->values()->all()) {
                throw (new ModelNotFoundException)->setModel(AuditIndicatorEvidence::class);
            }

            foreach ($evidenceIds as $index => $evidenceId) {
                $evidences[$evidenceId]->update(['sort_order' => ($index + 1) * 10]);
            }

            $project->activityLogs()->create([
                'user_id' => $actor->id,
                'activity' => 'audit_evidence.reordered',
                'description' => 'Urutan evidence diperbarui untuk '.$indicator->code.'.',
                'meta' => [
                    'indicator_id' => $indicator->id,
                    'ordered_evidence_ids' => array_values($evidenceIds),
                ],
            ]);
        });
    }
}
