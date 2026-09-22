<?php

namespace App\Services;

use App\Enums\ParameterType;
use App\Models\AuditProject;
use App\Models\AuditTemplate;
use App\Models\AuditTemplateParameter;

class TemplateSnapshotService
{
    /**
     * Copy the full master template structure into the project snapshot tables.
     * The snapshot is a content copy, never a live reference back to the master,
     * so later changes to the template cannot affect existing projects.
     *
     * @return array{principles: int, sections: int, indicators: int, parameters: int}
     */
    public function snapshot(AuditProject $project, AuditTemplate $template): array
    {
        $counts = ['principles' => 0, 'sections' => 0, 'indicators' => 0, 'parameters' => 0];

        foreach ($template->principles()->with(['sections.indicators.parameters'])->get() as $principle) {
            $counts['principles']++;

            $projectPrinciple = $project->snapshotPrinciples()->create([
                'source_template_principle_id' => $principle->id,
                'code' => $principle->code,
                'title' => $principle->title,
                'description' => $principle->description,
                'sort_order' => $principle->sort_order,
            ]);

            $sections = $principle->sections->sortBy('sort_order');

            $createTree = function (?int $masterParentId, ?int $projectParentId) use ($project, $projectPrinciple, $sections, &$createTree, &$counts): void {
                foreach ($sections->where('parent_id', $masterParentId) as $section) {
                    $projectSection = $project->snapshotSections()->create([
                        'audit_project_principle_id' => $projectPrinciple->id,
                        'parent_id' => $projectParentId,
                        'source_template_section_id' => $section->id,
                        'code' => $section->code,
                        'title' => $section->title,
                        'description' => $section->description,
                        'sort_order' => $section->sort_order,
                    ]);
                    $counts['sections']++;

                    foreach ($section->indicators->sortBy('sort_order') as $indicator) {
                        $projectIndicator = $project->snapshotIndicators()->create([
                            'audit_project_section_id' => $projectSection->id,
                            'source_template_indicator_id' => $indicator->id,
                            'code' => $indicator->code,
                            'title' => $indicator->title,
                            'description' => $indicator->description,
                            'indicator_type' => $indicator->indicator_type,
                            'sort_order' => $indicator->sort_order,
                        ]);
                        $counts['indicators']++;

                        foreach ($indicator->parameters->sortBy('sort_order') as $parameter) {
                            $project->snapshotParameters()->create([
                                'audit_project_indicator_id' => $projectIndicator->id,
                                'source_template_parameter_id' => $parameter->id,
                                'parameter_type' => $this->parameterTypeValue($parameter),
                                'number' => $parameter->number,
                                'content' => $parameter->content,
                                'sort_order' => $parameter->sort_order,
                            ]);
                            $counts['parameters']++;
                        }
                    }

                    $createTree($section->id, $projectSection->id);
                }
            };

            $createTree(null, null);
        }

        return $counts;
    }

    private function parameterTypeValue(AuditTemplateParameter $parameter): string
    {
        return $parameter->parameter_type instanceof ParameterType
            ? $parameter->parameter_type->value
            : (string) $parameter->parameter_type;
    }
}
