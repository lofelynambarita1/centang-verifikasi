<?php

namespace Database\Seeders;

use App\Services\TemplateJsonImporter;
use Illuminate\Database\Seeder;

class TemplateStructureSeeder extends Seeder
{
    /**
     * Seed the master template structure from the JSON data file.
     */
    public function run(): void
    {
        $template = app(TemplateJsonImporter::class)
            ->importFromConfig();

        $tree = $template->principles()->with([
            'sections.children', 'sections.indicators.parameters',
        ])->get();

        $principlesCount = $tree->count();
        $sectionsCount = $tree->flatMap->sections->count();
        $indicatorsCount = $tree->flatMap->sections->flatMap->indicators->count();
        $parametersCount = $tree->flatMap->sections->flatMap->indicators->flatMap->parameters->count();

        $this->command?->info(sprintf(
            'Struktur template "%s" (v%s) diimpor: %d prinsip, %d section, %d indikator, %d parameter.',
            $template->name,
            $template->version ?? '-',
            $principlesCount,
            $sectionsCount,
            $indicatorsCount,
            $parametersCount,
        ));
    }
}
