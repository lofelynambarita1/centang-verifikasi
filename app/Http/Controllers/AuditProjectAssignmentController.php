<?php

namespace App\Http\Controllers;

use App\Enums\ProjectRole;
use App\Http\Requests\BulkAssignmentRequest;
use App\Http\Requests\StoreAssignmentRequest;
use App\Models\AuditProject;
use App\Models\AuditProjectIndicator;
use App\Models\AuditProjectSection;
use App\Models\User;
use App\Services\AssignmentService;
use App\Services\AuditProgressService;
use App\Support\StructureTree;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AuditProjectAssignmentController extends Controller
{
    public function __construct(
        private readonly AssignmentService $assignments,
        private readonly StructureTree $tree,
        private readonly AuditProgressService $progress,
    ) {}

    /**
     * Show the PIC assignment page (Pembagian Auditor) for a project.
     */
    public function index(AuditProject $auditProject): View
    {
        $this->authorize('manageAssignments', $auditProject);

        $project = $auditProject->load('template', 'company', 'leadMember.user', 'members.user');
        $tree = $this->tree->fromProject($project);

        $indicatorCount = $project->snapshotIndicators()->count();
        $assignedCount = $project->assignments()->count();
        $principleSummaries = $this->principleAssignmentSummaries($project);
        $principleWorkloads = $project->members->map(function ($member) use ($principleSummaries) {
            $member->setAttribute('principles_count', collect($principleSummaries)
                ->filter(fn (array $summary): bool => $summary['primary_pic_id'] === $member->user_id)
                ->count());

            return $member;
        });

        return view('audit-projects.assignments', [
            'project' => $project,
            'tree' => $tree,
            'principleNodes' => collect($tree)->mapWithKeys(
                fn (array $node): array => [$node['model']->id => $node],
            ),
            'principleSummaries' => $principleSummaries,
            'members' => $project->members,
            'workloads' => $this->assignments->workloads($project),
            'principleWorkloads' => $principleWorkloads,
            'stats' => [
                'total' => $indicatorCount,
                'assigned' => $assignedCount,
                'unassigned' => $indicatorCount - $assignedCount,
                'total_principles' => count($principleSummaries),
                'assigned_principles' => count(array_filter($principleSummaries, fn (array $summary): bool => $summary['primary_pic_id'] !== null)),
                'unassigned_principles' => count(array_filter($principleSummaries, fn (array $summary): bool => $summary['primary_pic_id'] === null)),
            ],
            'indicatorCountsBySection' => $this->subtreeIndicatorCounts($project),
            'indicatorCountsByPrinciple' => $this->indicatorCountsByPrinciple($project),
            'canManage' => auth()->user()->can('manageAssignments', $project),
        ]);
    }

    /**
     * Build the compact, principle-level assignment view without changing the
     * indicator-level assignment source of truth.
     *
     * @return array<int, array{total: int, assigned: int, unassigned: int, primary_pic_id: ?int, pic_counts: array<int, array{name: string, total: int}>, exceptions: int}>
     */
    private function principleAssignmentSummaries(AuditProject $project): array
    {
        $summaries = [];

        foreach ($project->snapshotPrinciples()->get(['id']) as $principle) {
            $summaries[$principle->id] = [
                'total' => 0,
                'assigned' => 0,
                'unassigned' => 0,
                'primary_pic_id' => null,
                'pic_counts' => [],
                'exceptions' => 0,
            ];
        }

        $indicators = $project->snapshotIndicators()
            ->with([
                'section:id,audit_project_id,audit_project_principle_id',
                'assignment.pic:id,name',
            ])
            ->get();

        foreach ($indicators as $indicator) {
            $principleId = $indicator->section?->audit_project_principle_id;

            if ($principleId === null || ! isset($summaries[$principleId])) {
                continue;
            }

            $summary = &$summaries[$principleId];
            $summary['total']++;

            $pic = $indicator->assignment?->pic;

            if ($pic === null) {
                $summary['unassigned']++;
                unset($pic);

                continue;
            }

            $summary['assigned']++;
            $summary['pic_counts'][$pic->id] ??= ['name' => $pic->name, 'total' => 0];
            $summary['pic_counts'][$pic->id]['total']++;
            unset($pic);
        }

        foreach ($summaries as &$summary) {
            uasort($summary['pic_counts'], fn (array $left, array $right): int => $right['total'] <=> $left['total']);
            $primary = array_key_first($summary['pic_counts']);
            $summary['primary_pic_id'] = $primary !== null ? (int) $primary : null;
            $summary['exceptions'] = max(0, count($summary['pic_counts']) - 1);
        }
        unset($summary);

        return $summaries;
    }

    /**
     * Assign one indicator to a PIC.
     */
    public function store(StoreAssignmentRequest $request, AuditProject $auditProject, AuditProjectIndicator $indicator): RedirectResponse
    {
        $this->authorize('manageAssignments', $auditProject);

        $this->assignments->assign(
            $auditProject,
            $indicator,
            User::findOrFail($request->integer('pic_user_id')),
            $request->user(),
        );

        return back()->with('success', 'PIC indikator diperbarui.');
    }

    /**
     * Manually bulk-assign all descendant indicators of a Principle or Section.
     */
    public function bulk(BulkAssignmentRequest $request, AuditProject $auditProject): RedirectResponse
    {
        $this->authorize('manageAssignments', $auditProject);

        $pic = User::findOrFail($request->integer('pic_user_id'));

        $summary = $request->string('node_type')->toString() === 'principle'
            ? $this->assignments->assignPrinciple($auditProject, $request->integer('node_id'), $pic, $request->user())
            : $this->assignments->assignSection($auditProject, $request->integer('node_id'), $pic, $request->user());

        return back()->with('success', sprintf('Bulk assignment selesai: %d indikator ditugaskan kepada "%s".', $summary['total'], $pic->name));
    }

    /**
     * Automatically distribute all currently unassigned indicators.
     */
    public function autoAssign(Request $request, AuditProject $auditProject): RedirectResponse
    {
        $this->authorize('manageAssignments', $auditProject);

        $summary = $this->assignments->autoAssign($auditProject, $request->user());
        $participantCount = $auditProject->members()
            ->whereIn('project_role', [ProjectRole::LeadAuditor->value, ProjectRole::Auditor->value])
            ->count();

        return back()->with(
            'success',
            $summary['principles'] > 0
                ? sprintf('%d Prinsip berhasil dibagi ke %d auditor. %d indikator telah memiliki PIC.', $summary['principles'], $participantCount, $summary['indicators'])
                : 'Semua prinsip sudah memiliki pembagian atau pengecualian.',
        );
    }

    /**
     * Show the "Pekerjaan Saya" page: only the indicators the current user is
     * the PIC of.
     */
    public function myWork(AuditProject $auditProject): View
    {
        $this->authorize('view', $auditProject);

        $indicators = $auditProject->snapshotIndicators()
            ->whereHas('assignment', fn ($assignment) => $assignment->where('pic_user_id', auth()->id()))
            ->with(['section.principle', 'assignment.pic', 'result'])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('audit-projects.my-work', [
            'project' => $auditProject->load('template', 'company'),
            'indicators' => $indicators,
            'summary' => $this->progress->userSummary($auditProject, auth()->user()),
        ]);
    }

    /**
     * Read-only detail of a single project indicator with its Parameters and
     * Verifiers. Only admins, the Lead Auditor and the indicator's PIC may open
     * it.
     */
    public function showIndicator(AuditProject $auditProject, AuditProjectIndicator $indicator): View
    {
        $this->authorize('view', $auditProject);

        abort_if($indicator->audit_project_id !== $auditProject->id, 404);

        $user = auth()->user();

        $isPic = $auditProject->assignments()
            ->where('audit_project_indicator_id', $indicator->id)
            ->where('pic_user_id', $user->id)
            ->exists();

        if (! $user->system_role->isAdminOrAbove()
            && ! $auditProject->userIsLead($user)
            && ! $isPic) {
            abort(403);
        }

        $indicator->load(['section.principle', 'parameters']);

        return view('audit-projects.indicator', [
            'project' => $auditProject->load('template', 'company'),
            'indicator' => $indicator,
        ]);
    }

    /**
     * Indicator count per Principle (all sections belonging to the principle).
     *
     * @return array<int, int>
     */
    private function indicatorCountsByPrinciple(AuditProject $project): array
    {
        $byPrinciple = [];

        foreach ($project->snapshotPrinciples()->get(['id']) as $principle) {
            $sectionIds = AuditProjectSection::query()
                ->where('audit_project_id', $project->id)
                ->where('audit_project_principle_id', $principle->id)
                ->pluck('id');

            $byPrinciple[$principle->id] = AuditProjectIndicator::query()
                ->where('audit_project_id', $project->id)
                ->whereIn('audit_project_section_id', $sectionIds)
                ->count();
        }

        return $byPrinciple;
    }

    /**
     * Indicator count per Section including all nested child sections.
     *
     * @return array<int, int>
     */
    private function subtreeIndicatorCounts(AuditProject $project): array
    {
        $all = AuditProjectSection::query()
            ->where('audit_project_id', $project->id)
            ->get(['id', 'parent_id']);

        $direct = AuditProjectIndicator::query()
            ->where('audit_project_id', $project->id)
            ->selectRaw('audit_project_section_id, COUNT(*) as total')
            ->groupBy('audit_project_section_id')
            ->pluck('total', 'audit_project_section_id');

        $memo = [];

        $count = function (int $sectionId) use (&$count, $all, $direct, &$memo): int {
            if (isset($memo[$sectionId])) {
                return $memo[$sectionId];
            }

            $total = (int) ($direct[$sectionId] ?? 0);

            foreach ($all as $row) {
                if ($row->parent_id === $sectionId) {
                    $total += $count($row->id);
                }
            }

            return $memo[$sectionId] = $total;
        };

        $result = [];

        foreach ($all as $row) {
            $result[$row->id] = $count($row->id);
        }

        return $result;
    }
}
