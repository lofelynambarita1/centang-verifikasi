<?php

namespace App\Services;

use App\Models\AuditTemplate;
use App\Models\AuditTemplatePrinciple;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;

class TemplateJsonImporter
{
    /**
     * Import the canonical ISPO structure from the application configuration.
     */
    public function importFromConfig(?User $actor = null): AuditTemplate
    {
        $config = config('ispo');
        $principles = [];

        foreach ($config['principles'] as $principleCode => $principleTitle) {
            $sections = [];

            foreach ($config['criteria'] as $criteriaCode => $criteria) {
                if (! str_starts_with($criteriaCode, "{$principleCode}.") || substr_count($criteriaCode, '.') !== 1) {
                    continue;
                }

                $indicators = [];

                foreach ($config['indicators'] as $indicator) {
                    if ($indicator['criteria'] !== $criteriaCode) {
                        continue;
                    }

                    $parameters = [];
                    foreach (array_values($indicator['parameters']) as $sortOrder => $content) {
                        $parameters[] = [
                            'parameter_type' => 'parameter',
                            'number' => null,
                            'content' => $content,
                            'sort_order' => $sortOrder,
                        ];
                    }

                    $indicators[] = [
                        'code' => $indicator['code'],
                        'title' => $indicator['title'],
                        'description' => null,
                        'indicator_type' => null,
                        'sort_order' => count($indicators),
                        'parameters' => $parameters,
                    ];
                }

                $sections[] = [
                    'code' => $criteriaCode,
                    'title' => $criteria['title'],
                    'description' => $criteria['description'] ?: null,
                    'sort_order' => count($sections),
                    'sections' => [],
                    'indicators' => $indicators,
                ];
            }

            $principles[] = [
                'code' => (string) $principleCode,
                'title' => $principleTitle,
                'description' => null,
                'sort_order' => count($principles),
                'sections' => $sections,
            ];
        }

        return $this->importFromArray([
            '_meta' => [
                'template_code' => 'FrO.8603/GIS-1.1',
                'template_scheme' => 'ISPO',
                'template_name' => 'Laporan Verifikasi Hasil ISPO',
                'template_version' => '1.1',
            ],
            'principles' => $principles,
        ], $actor);
    }

    /**
     * Import a template structure from a JSON file into the master tables.
     * The import is idempotent: running it again replaces the structure of the
     * same template with the exact content of the file.
     */
    public function importFromPath(string $path, ?User $actor = null): AuditTemplate
    {
        if (! File::exists($path)) {
            throw new RuntimeException("File data template tidak ditemukan: {$path}");
        }

        return $this->importFromArray(json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR), $actor);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function importFromArray(array $data, ?User $actor = null): AuditTemplate
    {
        $meta = $data['_meta'] ?? [];

        if (empty($meta['template_code'])) {
            throw new RuntimeException('Meta template_code wajib ada pada file struktur template.');
        }

        $template = AuditTemplate::updateOrCreate(
            ['code' => $meta['template_code']],
            [
                'scheme' => $meta['template_scheme'] ?? null,
                'name' => $meta['template_name'] ?? $meta['template_code'],
                'version' => $meta['template_version'] ?? null,
                'description' => $meta['dataset_status'] ?? null,
                'is_active' => true,
                'created_by' => $actor?->id,
            ],
        );

        DB::transaction(function () use ($template, $data): void {
            $template->principles()->delete();
            $this->replaceStructure($template, $data['principles'] ?? []);
        });

        return $template;
    }

    /**
     * @param  array<int, array<string, mixed>>  $principles
     */
    private function replaceStructure(AuditTemplate $template, array $principles): void
    {
        foreach ($principles as $principleData) {
            $template->principles()->where('code', $principleData['code'])->delete();

            $principle = $template->principles()->create([
                'code' => $principleData['code'],
                'title' => $principleData['title'],
                'description' => $principleData['description'] ?? null,
                'sort_order' => $principleData['sort_order'] ?? 0,
            ]);

            $this->createSections($principle, $principleData['sections'] ?? [], null);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $sections
     */
    private function createSections(AuditTemplatePrinciple $principle, array $sections, ?int $parentId): void
    {
        foreach ($sections as $sectionData) {
            $section = $principle->sections()->create([
                'parent_id' => $parentId,
                'code' => $sectionData['code'],
                'title' => $sectionData['title'],
                'description' => $sectionData['description'] ?? null,
                'sort_order' => $sectionData['sort_order'] ?? 0,
            ]);

            $this->createSections($principle, $sectionData['sections'] ?? [], $section->id);

            foreach ($sectionData['indicators'] ?? [] as $indicatorData) {
                $indicator = $section->indicators()->create([
                    'code' => $indicatorData['code'],
                    'title' => $indicatorData['title'],
                    'description' => $indicatorData['description'] ?? null,
                    'indicator_type' => $indicatorData['indicator_type'] ?? null,
                    'sort_order' => $indicatorData['sort_order'] ?? 0,
                ]);

                foreach ($indicatorData['parameters'] ?? [] as $parameterData) {
                    $indicator->parameters()->create([
                        'parameter_type' => $parameterData['parameter_type'],
                        'number' => $parameterData['number'] ?? null,
                        'content' => $parameterData['content'],
                        'sort_order' => $parameterData['sort_order'] ?? 0,
                    ]);
                }
            }
        }
    }
}
