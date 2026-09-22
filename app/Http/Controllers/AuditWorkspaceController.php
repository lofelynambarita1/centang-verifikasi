<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveDraftResultRequest;
use App\Http\Requests\SubmitAuditResultRequest;
use App\Models\AuditIndicatorResult;
use App\Models\AuditProject;
use App\Models\AuditProjectIndicator;
use App\Models\User;
use App\Services\AuditResultService;
use App\Services\AuditResultWorkflowService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class AuditWorkspaceController extends Controller
{
    public function __construct(
        private readonly AuditResultService $results,
        private readonly AuditResultWorkflowService $workflow,
    ) {}

    /**
     * Open the auditor workspace for one indicator. Read-only context plus the
     * Hasil Audit, editable only for the active PIC while the project is
     * in-progress.
     */
    public function show(AuditProject $auditProject, AuditProjectIndicator $indicator): View
    {
        abort_if($indicator->audit_project_id !== $auditProject->id, 404);

        $this->authorize('view', $indicator);

        $project = $auditProject->load('template', 'company', 'leadMember.user');
        $indicator->load(['section.principle', 'parameters', 'assignment.pic', 'activeEvidences.uploader']);

        $result = AuditIndicatorResult::query()
            ->where('audit_project_indicator_id', $indicator->id)
            ->with(['versions.changedBy', 'submitter'])
            ->first();

        $user = auth()->user();

        return view('audit-projects.workspace', [
            'project' => $project,
            'indicator' => $indicator,
            'result' => $result,
            'canEdit' => $user->can('update', $indicator),
            'canSubmit' => $result !== null
                && $result->workflow_status?->value === 'draft'
                && $result->assessment_status !== null
                && trim((string) $result->result_text) !== ''
                && $user->can('update', $indicator),
            'versions' => $result?->versions->sortByDesc('version_number')->take(3) ?? collect(),
            'navigation' => $this->navigation($project, $user, $indicator),
            'evidences' => $indicator->activeEvidences->sortBy([
                ['sort_order', 'asc'],
                ['id', 'asc'],
            ]),
        ]);
    }

    /**
     * Persist (create or update) the draft Hasil Audit of an indicator.
     */
    public function saveDraft(SaveDraftResultRequest $request, AuditProject $auditProject, AuditProjectIndicator $indicator): RedirectResponse
    {
        abort_if($indicator->audit_project_id !== $auditProject->id, 404);

        $this->authorize('update', $indicator);

        $this->results->saveDraft(
            $auditProject,
            $indicator,
            $request->user(),
            $request->string('result_text')->trim()->toString(),
            $request->expectedLockVersion(),
            $request->assessmentStatus(),
        );

        return back()->with('success', 'Draft Hasil Audit disimpan.');
    }

    public function autosave(SaveDraftResultRequest $request, AuditProject $auditProject, AuditProjectIndicator $indicator): JsonResponse
    {
        abort_if($indicator->audit_project_id !== $auditProject->id, 404);

        $this->authorize('update', $indicator);

        try {
            $result = $this->results->saveDraft(
                $auditProject,
                $indicator,
                $request->user(),
                $request->string('result_text')->trim()->toString(),
                $request->expectedLockVersion(),
                $request->assessmentStatus(),
            );
        } catch (ValidationException $exception) {
            $errors = $exception->errors();

            if (array_key_exists('lock_version', $errors)) {
                return response()->json([
                    'conflict' => true,
                    'message' => $errors['lock_version'][0],
                ], 409);
            }

            return response()->json([
                'message' => 'Autosave gagal.',
                'errors' => $errors,
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'noop' => $result->lock_version === $request->expectedLockVersion(),
            'lock_version' => $result->lock_version,
            'saved_at' => now()->toISOString(),
        ]);
    }

    public function submit(
        SubmitAuditResultRequest $request,
        AuditProject $auditProject,
        AuditProjectIndicator $indicator,
    ): RedirectResponse {
        abort_if($indicator->audit_project_id !== $auditProject->id, 404);

        $this->workflow->submit(
            $auditProject,
            $indicator,
            $request->user(),
            $request->expectedLockVersion(),
        );

        return back()->with('success', 'Hasil Audit dikirim untuk review.');
    }

    /**
     * Previous/next workspace navigation restricted to the current user's own
     * PIC indicators. Other auditors' indicators never appear here.
     *
     * @return array{prev?: AuditProjectIndicator, next?: AuditProjectIndicator}
     */
    private function navigation(AuditProject $project, User $user, AuditProjectIndicator $current): array
    {
        $ids = $project->snapshotIndicators()
            ->whereHas('assignment', fn ($assignment) => $assignment->where('pic_user_id', $user->id))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->pluck('id')
            ->values();

        $index = $ids->search($current->id);

        if ($index === false) {
            return [];
        }

        $navigation = [];

        if ($index > 0) {
            $navigation['prev'] = AuditProjectIndicator::findOrFail($ids[$index - 1]);
        }

        if ($index < $ids->count() - 1) {
            $navigation['next'] = AuditProjectIndicator::findOrFail($ids[$index + 1]);
        }

        return $navigation;
    }
}
