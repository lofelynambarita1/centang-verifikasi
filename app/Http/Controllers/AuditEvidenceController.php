<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAuditEvidenceRequest;
use App\Models\AuditIndicatorEvidence;
use App\Models\AuditProject;
use App\Models\AuditProjectIndicator;
use App\Services\AuditEvidenceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class AuditEvidenceController extends Controller
{
    public function __construct(private readonly AuditEvidenceService $evidences) {}

    public function store(
        StoreAuditEvidenceRequest $request,
        AuditProject $auditProject,
        AuditProjectIndicator $indicator,
    ): RedirectResponse {
        abort_if($indicator->audit_project_id !== $auditProject->id, 404);

        $this->evidences->store($auditProject, $indicator, $request->user(), $request->file('evidence'));

        return back()->with('success', 'Evidence berhasil diunggah.');
    }

    public function download(
        AuditProject $auditProject,
        AuditProjectIndicator $indicator,
        AuditIndicatorEvidence $evidence,
    ): Response {
        abort_if($evidence->audit_project_id !== $auditProject->id, 404);

        $this->authorize('view', $evidence);

        $disk = Storage::disk($evidence->storage_disk);

        abort_unless($disk->exists($evidence->storage_path), 404);
        $downloadName = str_replace(["\r", "\n", '"'], '', basename($evidence->original_name));

        if (config('filesystems.disks.'.$evidence->storage_disk.'.driver') === 'local') {
            return response()->download($disk->path($evidence->storage_path), $downloadName, [
                'Content-Type' => $evidence->mime_type,
            ]);
        }

        return response()->stream(function () use ($disk, $evidence): void {
            $stream = $disk->readStream($evidence->storage_path);

            if ($stream === false) {
                abort(404);
            }

            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => $evidence->mime_type,
            'Content-Disposition' => "attachment; filename*=UTF-8''".rawurlencode($downloadName),
        ]);
    }
}
