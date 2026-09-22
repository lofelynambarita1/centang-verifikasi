<?php

namespace App\Support;

use App\Models\AuditProject;
use App\Models\AuditProjectIndicator;
use App\Models\AuditProjectSection;
use App\Models\AuditTemplate;
use App\Models\AuditTemplateIndicator;
use App\Models\AuditTemplateSection;
use Illuminate\Database\Eloquent\Collection;

/**
 * Build nested tree arrays used to render the Principle → Section → Indicator →
 * Parameter structure. Nodes are shaped as ['model' => ..., 'children' => [...]].
 */
class StructureTree
{
    /**
     * @return array<int, array{model: mixed, children: array}>
     */
    public function fromTemplate(AuditTemplate $template): array
    {
        $tree = [];

        foreach ($template->principles()->with(['sections.children', 'sections.indicators.parameters'])->get() as $principle) {
            $tree[] = [
                'model' => $principle,
                'children' => $this->templateSections($principle->sections),
            ];
        }

        return $tree;
    }

    /**
     * @return array<int, array{model: mixed, children: array}>
     */
    public function fromProject(AuditProject $project): array
    {
        $tree = [];

        foreach ($project->snapshotPrinciples()->with(['sections.children', 'sections.indicators.parameters', 'sections.indicators.assignment.pic'])->get() as $principle) {
            $tree[] = [
                'model' => $principle,
                'children' => $this->projectSections($principle->sections),
            ];
        }

        return $tree;
    }

    /**
     * @param  Collection<int, AuditTemplateSection>  $sections
     * @return array<int, array{model: mixed, children: array}>
     */
    private function templateSections(Collection $sections): array
    {
        return $sections->where('parent_id', null)->sortBy('sort_order')->map(
            fn (AuditTemplateSection $section): array => $this->templateSection($section),
        )->values()->all();
    }

    /**
     * @return array<int, array{model: mixed, children: array}>
     */
    private function templateSection(AuditTemplateSection $section): array
    {
        $children = $section->children->sortBy('sort_order')->map(
            fn (AuditTemplateSection $child): array => $this->templateSection($child),
        )->values()->all();

        foreach ($section->indicators->sortBy('sort_order') as $indicator) {
            $children[] = ['model' => $indicator, 'children' => $this->templateParameters($indicator)];
        }

        return ['model' => $section, 'children' => $children];
    }

    /**
     * @return array<int, array{model: mixed, children: array}>
     */
    private function templateParameters(AuditTemplateIndicator $indicator): array
    {
        return $indicator->parameters->sortBy('sort_order')->map(
            fn ($parameter): array => ['model' => $parameter, 'children' => []],
        )->all();
    }

    /**
     * @param  Collection<int, AuditProjectSection>  $sections
     * @return array<int, array{model: mixed, children: array}>
     */
    private function projectSections(Collection $sections): array
    {
        return $sections->where('parent_id', null)->sortBy('sort_order')->map(
            fn (AuditProjectSection $section): array => $this->projectSection($section),
        )->values()->all();
    }

    /**
     * @return array<int, array{model: mixed, children: array}>
     */
    private function projectSection(AuditProjectSection $section): array
    {
        $children = $section->children->sortBy('sort_order')->map(
            fn (AuditProjectSection $child): array => $this->projectSection($child),
        )->values()->all();

        foreach ($section->indicators->sortBy('sort_order') as $indicator) {
            $children[] = ['model' => $indicator, 'children' => $this->projectParameters($indicator)];
        }

        return ['model' => $section, 'children' => $children];
    }

    /**
     * @return array<int, array{model: mixed, children: array}>
     */
    private function projectParameters(AuditProjectIndicator $indicator): array
    {
        return $indicator->parameters->sortBy('sort_order')->map(
            fn ($parameter): array => ['model' => $parameter, 'children' => []],
        )->all();
    }
}
