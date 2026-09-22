<?php

namespace App\Http\Controllers;

use App\Http\Requests\AutoAssignmentRequest;
use App\Http\Requests\BulkAssignmentRequest;
use App\Http\Requests\StoreAssignmentRequest;
use App\Models\AuditProject;
use App\Models\AuditProjectIndicator;
use App\Models\AuditProjectSection;
use App\Models\User;
use App\Services\AssignmentService;
use App\Support\StructureTree;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class AuditProjectAssignmentController extends Controller
{
    public function __construct(
        private readonly AssignmentService $assignments,
        private readonly StructureTree $tree,
    ) {}

    /**
     * Show the PIC assignment page (Pembagian Auditor) for a project.
     */
    public function index(AuditProject $auditProject): View
    {
        $this->authorize('view', $auditProject);

        $project = $auditProject->load('template', 'company', 'leadMember.user', 'members.user');

        $indicatorCount = $project->snapshotIndicators()->count();
        $assignedCount = $project->assignments()->count();

        return view('audit-projects.assignments', [
            'project' => $project,
            'tree' => $this->tree->fromProject($project),
            'members' => $project->members,
            'workloads' => $this->assignments->workloads($project),
            'stats' => [
                'total' => $indicatorCount,
                'assigned' => $assignedCount,
                'unassigned' => $indicatorCount - $assignedCount,
            ],
            'indicatorCountsBySection' => $this->subtreeIndicatorCounts($project),
            'indicatorCountsByPrinciple' => $this->indicatorCountsByPrinciple($project),
            'canManage' => auth()->user()->can('manageAssignments', $project),
        ]);
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
    public function autoAssign(AutoAssignmentRequest $request, AuditProject $auditProject): RedirectResponse
    {
        $this->authorize('manageAssignments', $auditProject);

        $picUserIds = $request->collect('pic_user_ids')->map(fn ($id): int => (int) $id)->all();

        $count = $this->assignments->autoAssign($auditProject, $picUserIds, $request->user());

        return back()->with('success', sprintf('%d indikator dibagi otomatis ke auditor terpilih.', $count));
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
            ->with(['section.principle', 'assignment.pic'])
            ->orderBy('sort_order')
            ->get();

        return view('audit-projects.my-work', [
            'project' => $auditProject->load('template', 'company'),
            'indicators' => $indicators,
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
