<?php

namespace Tests\Feature;

use App\Models\AuditTemplate;
use App\Models\User;
use App\Services\TemplateJsonImporter;
use App\Support\StructureTree;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditTemplateStructureTest extends TestCase
{
    use RefreshDatabase;

    private function makeTemplate(): AuditTemplate
    {
        $template = AuditTemplate::factory()->create();

        $p1 = $template->principles()->create(['code' => '1', 'title' => 'Prinsip Satu', 'sort_order' => 2]);
        $s1 = $p1->sections()->create(['code' => '1.1', 'title' => 'Kriteria 1', 'sort_order' => 20]);
        $s1b = $p1->sections()->create([
            'code' => '1.1.1', 'title' => 'Sub Kriteria', 'parent_id' => $s1->id, 'sort_order' => 10,
        ]);
        $i1 = $s1->indicators()->create(['code' => '1.1.2', 'title' => 'Indikator 1', 'sort_order' => 5]);
        $i1->parameters()->create([
            'parameter_type' => 'parameter', 'number' => '1', 'content' => 'Parameter 1', 'sort_order' => 2,
        ]);
        $i1->parameters()->create(['parameter_type' => 'verifier', 'content' => 'Verifier 1', 'sort_order' => 1]);
        $i2 = $s1b->indicators()->create(['code' => '1.1.1.1', 'title' => 'Indikator Sub', 'sort_order' => 1]);
        $i2->parameters()->create(['parameter_type' => 'parameter', 'content' => 'Parameter 2', 'sort_order' => 9]);

        $p2 = $template->principles()->create(['code' => '2', 'title' => 'Prinsip Dua', 'sort_order' => 1]);
        $s2 = $p2->sections()->create(['code' => '2.1', 'title' => 'Kriteria 2', 'sort_order' => 1]);
        $i3 = $s2->indicators()->create(['code' => '2.1.1', 'title' => 'Indikator 3', 'sort_order' => 3]);
        $i3->parameters()->create(['parameter_type' => 'verifier', 'content' => 'Verifier 2', 'sort_order' => 4]);

        return $template;
    }

    public function test_master_template_hierarchy_relations_resolve(): void
    {
        $template = $this->makeTemplate();

        $this->assertSame(2, $template->principles()->count());

        $principle = $template->principles()->where('code', '1')->firstOrFail();
        $this->assertSame(2, $principle->sections()->count());

        $section = $principle->sections()->where('code', '1.1')->firstOrFail();
        $this->assertNull($section->parent_id);
        $this->assertSame(1, $section->children()->count());
        $this->assertSame('1.1.1', $section->children->first()->code);

        $child = $principle->sections()->where('code', '1.1.1')->firstOrFail();
        $this->assertSame($section->id, $child->parent_id);
        $this->assertSame(1, $child->indicators()->count());

        $indicator = $child->indicators()->firstOrFail();
        $this->assertSame(1, $indicator->parameters()->count());
        $this->assertSame('Parameter 2', $indicator->parameters->first()->content);
    }

    public function test_structure_tree_nests_sections_before_indicators(): void
    {
        $template = $this->makeTemplate();
        $tree = app(StructureTree::class)->fromTemplate($template);

        $this->assertCount(2, $tree);

        $firstPrinciple = $tree[0];
        $this->assertSame('2', $firstPrinciple['model']->code);

        $secondPrinciple = $tree[1];
        $this->assertSame('1', $secondPrinciple['model']->code);
        $this->assertCount(1, $secondPrinciple['children']);
        $kriteria = $secondPrinciple['children'][0];
        $this->assertSame('1.1', $kriteria['model']->code);
        $this->assertSame('1.1.1', $kriteria['children'][0]['model']->code);
        $this->assertSame('1.1.2', $kriteria['children'][1]['model']->code);
        $this->assertCount(1, $kriteria['children'][0]['children']);
        $this->assertSame('1.1.1.1', $kriteria['children'][0]['children'][0]['model']->code);
    }

    public function test_json_importer_is_idempotent(): void
    {
        $data = [
            '_meta' => [
                'template_code' => 'ISO-9001-TEST',
                'template_scheme' => 'ISO 9001',
                'template_name' => 'Template Uji',
                'template_version' => '1.0',
            ],
            'principles' => [
                [
                    'code' => '1',
                    'title' => 'Prinsip 1',
                    'sections' => [
                        [
                            'code' => '1.1',
                            'title' => 'Kriteria 1',
                            'sections' => [
                                ['code' => '1.1.1', 'title' => 'Sub Kriteria', 'sections' => [], 'indicators' => []],
                            ],
                            'indicators' => [
                                [
                                    'code' => '1.1.2',
                                    'title' => 'Indikator 1',
                                    'parameters' => [
                                        ['parameter_type' => 'parameter', 'number' => '1', 'content' => 'A', 'sort_order' => 1],
                                        ['parameter_type' => 'verifier', 'content' => 'B', 'sort_order' => 2],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $importer = app(TemplateJsonImporter::class);
        $template = $importer->importFromArray($data);

        $this->assertSame(1, $template->principles()->count());

        $importer->importFromArray($data);

        $this->assertSame(1, $template->refresh()->principles()->count());
        $this->assertSame(2, $template?->principles()->first()?->sections()->count());
        $this->assertSame(2, $template?->principles()->first()?->sections()->count());
        $this->assertSame(1, $template?->principles()->first()?->sections()->first()?->indicators()->count());
    }

    public function test_auditor_cannot_create_a_template_structure_item(): void
    {
        $template = $this->makeTemplate();
        $auditor = User::factory()->auditor()->create();

        $this->actingAs($auditor)
            ->post(route('templates.structure.store', [$template, 'principle']), [
                'code' => '9', 'title' => 'Prinsip Terlarang', 'sort_order' => 0,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('audit_template_principles', ['title' => 'Prinsip Terlarang']);
    }

    public function test_auditor_can_view_the_template_structure(): void
    {
        $template = $this->makeTemplate();
        $auditor = User::factory()->auditor()->create();

        $this->actingAs($auditor)
            ->get(route('templates.structure.index', $template))
            ->assertOk()
            ->assertSee('Prinsip Satu')
            ->assertSee('Sub Kriteria');
    }

    public function test_admin_can_create_every_entity_type_via_web(): void
    {
        $admin = User::factory()->admin()->create();
        $template = AuditTemplate::factory()->create();
        $p1 = $template->principles()->create(['code' => '1', 'title' => 'Prinsip', 'sort_order' => 0]);

        $this->actingAs($admin)
            ->post(route('templates.structure.store', [$template, 'section']), [
                'principle_id' => $p1->id, 'code' => '1.1', 'title' => 'Kriteria', 'sort_order' => 1,
            ])
            ->assertRedirect(route('templates.structure.index', $template));

        $section = $p1->sections()->where('code', '1.1')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('templates.structure.store', [$template, 'indicator']), [
                'section_id' => $section->id, 'code' => '1.1.1', 'title' => 'Indikator', 'sort_order' => 2,
            ])
            ->assertRedirect(route('templates.structure.index', $template));

        $indicator = $section->indicators()->where('code', '1.1.1')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('templates.structure.store', [$template, 'parameter']), [
                'indicator_id' => $indicator->id,
                'parameter_type' => 'verifier',
                'content' => 'Dokumen bukti',
                'sort_order' => 0,
            ])
            ->assertRedirect(route('templates.structure.index', $template));

        $this->assertSame(1, $indicator->parameters()->count());
        $this->assertSame('verifier', $indicator->parameters()->first()->parameter_type->value);
    }

    public function test_code_must_be_unique_within_its_parent_scope(): void
    {
        $admin = User::factory()->admin()->create();
        $template = $this->makeTemplate();
        $principle = $template->principles()->where('code', '1')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('templates.structure.store', [$template, 'section']), [
                'principle_id' => $principle->id, 'code' => '1.1', 'title' => 'Kriteria duplikat', 'sort_order' => 0,
            ])
            ->assertSessionHasErrors('code');
    }

    public function test_parent_of_a_section_cannot_be_changed_on_update(): void
    {
        $admin = User::factory()->admin()->create();
        $template = $this->makeTemplate();
        $principle = $template->principles()->where('code', '1')->firstOrFail();
        $child = $principle->sections()->where('code', '1.1.1')->firstOrFail();
        $newParent = $principle->sections()->where('code', '1.1')->firstOrFail();

        $this->actingAs($admin)
            ->put(route('templates.structure.update', [$template, 'section', $child->id]), [
                'principle_id' => $principle->id,
                'parent_id' => $newParent->id,
                'code' => '1.1.2',
                'title' => 'Sub Kriteria baru',
                'sort_order' => 0,
            ])
            ->assertRedirect(route('templates.structure.index', $template));

        $child->refresh();

        $this->assertSame($newParent->id, $child->parent_id);
        $this->assertSame('1.1.2', $child->code);
        $this->assertSame('Sub Kriteria baru', $child->title);
    }

    public function test_deleting_a_principle_cascades_to_all_descendants(): void
    {
        $admin = User::factory()->admin()->create();
        $template = $this->makeTemplate();
        $principle = $template->principles()->where('code', '1')->firstOrFail();

        $this->actingAs($admin)
            ->delete(route('templates.structure.destroy', [$template, 'principle', $principle->id]))
            ->assertRedirect(route('templates.structure.index', $template));

        $this->assertDatabaseMissing('audit_template_principles', ['id' => $principle->id]);
        $this->assertSame(0, $principle->sections()->count());
    }

    public function test_move_up_swaps_sort_order_with_an_adjacent_sibling(): void
    {
        $admin = User::factory()->admin()->create();
        $template = AuditTemplate::factory()->create();
        $p1 = $template->principles()->create(['code' => '1', 'title' => 'Satu', 'sort_order' => 10]);
        $p2 = $template->principles()->create(['code' => '2', 'title' => 'Dua', 'sort_order' => 20]);

        $this->actingAs($admin)
            ->put(route('templates.structure.move', [$template, 'principle', $p2->id]), ['direction' => 'up'])
            ->assertRedirect(route('templates.structure.index', $template));

        $this->assertSame(10, $p2->refresh()->sort_order);
        $this->assertSame(20, $p1->refresh()->sort_order);
    }

    public function test_editing_a_parameter_does_not_change_its_indicator(): void
    {
        $admin = User::factory()->admin()->create();
        $template = $this->makeTemplate();
        $indicator = $template->principles()->where('code', '1')->firstOrFail()
            ->sections()->where('code', '1.1')->firstOrFail()
            ->indicators()->where('code', '1.1.2')->firstOrFail();
        $parameter = $indicator->parameters()->where('parameter_type', 'parameter')->firstOrFail();
        $indicatorId = $parameter->indicator_id;

        $this->actingAs($admin)
            ->put(route('templates.structure.update', [$template, 'parameter', $parameter->id]), [
                'indicator_id' => $indicator->id,
                'parameter_type' => 'parameter',
                'number' => '2',
                'content' => 'Parameter 1 revisi',
                'sort_order' => 5,
            ])
            ->assertRedirect(route('templates.structure.index', $template));

        $parameter->refresh();

        $this->assertSame($indicatorId, $parameter->indicator_id);
        $this->assertSame('2', $parameter->number);
        $this->assertSame('Parameter 1 revisi', $parameter->content);
    }
}
