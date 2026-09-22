<?php

namespace App\Http\Controllers;

use App\Models\AuditProject;
use App\Support\StructureTree;
use Illuminate\Contracts\View\View;

class AuditProjectStructureController extends Controller
{
    public function __construct(private readonly StructureTree $tree) {}

    /**
     * Show the read-only snapshot structure of a project.
     */
    public function show(AuditProject $auditProject): View
    {
        $this->authorize('view', $auditProject);

        $auditProject->load('template', 'company');

        return view('audit-projects.structure', [
            'project' => $auditProject,
            'tree' => $this->tree->fromProject($auditProject),
        ]);
    }
}
