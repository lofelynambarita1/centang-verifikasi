<?php

namespace App\Http\Controllers;

use App\Http\Requests\RemoveAuditEvidenceRequest;
use App\Http\Requests\ReorderAuditEvidenceRequest;
use App\Http\Requests\ReplaceAuditEvidenceRequest;
use App\Http\Requests\StoreAuditEvidenceRequest;
use App\Http\Requests\UpdateAuditEvidenceMetadataRequest;
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

    public function update(
        UpdateAuditEvidenceMetadataRequest $request,
        AuditProject $auditProject,
        AuditProjectIndicator $indicator,
        AuditIndicatorEvidence $evidence,
    ): RedirectResponse {
        abort_if($evidence->audit_project_id !== $auditProject->id, 404);
        abort_if($evidence->audit_project_indicator_id !== $indicator->id, 404);

        $this->evidences->updateMetadata(
            $auditProject,
            $indicator,
            $evidence,
            $request->user(),
            $request->validated('caption'),
            (bool) $request->boolean('is_report_included'),
        );

        return back()->with('success', 'Metadata evidence disimpan.');
    }

    public function reorder(
        ReorderAuditEvidenceRequest $request,
        AuditProject $auditProject,
        AuditProjectIndicator $indicator,
    ): RedirectResponse {
        $this->evidences->reorder($auditProject, $indicator, $request->user(), $request->validated('evidence_ids'));

        return back()->with('success', 'Urutan evidence disimpan.');
    }

    public function replace(
        ReplaceAuditEvidenceRequest $request,
        AuditProject $auditProject,
        AuditProjectIndicator $indicator,
        AuditIndicatorEvidence $evidence,
    ): RedirectResponse {
        $this->ensureEvidenceScope($auditProject, $indicator, $evidence);

        $this->evidences->replace(
            $auditProject,
            $indicator,
            $evidence,
            $request->user(),
            $request->file('evidence'),
        );

        return back()->with('success', 'Evidence berhasil diganti dan jejak audit lama dipertahankan.');
    }

    public function remove(
        RemoveAuditEvidenceRequest $request,
        AuditProject $auditProject,
        AuditProjectIndicator $indicator,
        AuditIndicatorEvidence $evidence,
    ): RedirectResponse {
        $this->ensureEvidenceScope($auditProject, $indicator, $evidence);

        $this->evidences->remove(
            $auditProject,
            $indicator,
            $evidence,
            $request->user(),
            $request->validated('removal_reason'),
        );

        return back()->with('success', 'Evidence dihapus secara logis. File fisik tetap dipertahankan untuk audit trail.');
    }

    public function download(
        AuditProject $auditProject,
        AuditProjectIndicator $indicator,
        AuditIndicatorEvidence $evidence,
    ): Response {
        $this->ensureEvidenceScope($auditProject, $indicator, $evidence);

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

    private function ensureEvidenceScope(
        AuditProject $project,
        AuditProjectIndicator $indicator,
        AuditIndicatorEvidence $evidence,
    ): void {
        abort_if(
            $indicator->audit_project_id !== $project->id
                || $evidence->audit_project_id !== $project->id
                || $evidence->audit_project_indicator_id !== $indicator->id,
            404,
        );
    }
}
