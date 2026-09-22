<?php

namespace App\Services;

use App\Models\AuditIndicatorEvidence;
use App\Models\AuditProject;
use App\Models\AuditProjectIndicator;
use App\Models\User;
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
}
