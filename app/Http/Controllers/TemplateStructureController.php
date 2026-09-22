<?php

namespace App\Http\Controllers;

use App\Http\Requests\TemplateStructureRequest;
use App\Models\AuditTemplate;
use App\Models\AuditTemplateIndicator;
use App\Models\AuditTemplateParameter;
use App\Models\AuditTemplatePrinciple;
use App\Models\AuditTemplateSection;
use App\Support\StructureTree;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TemplateStructureController extends Controller
{
    private const TYPES = ['principle', 'section', 'indicator', 'parameter'];

    public function __construct(private readonly StructureTree $tree) {}

    /**
     * Show the structure tree of a template.
     */
    public function index(AuditTemplate $template): View
    {
        $this->authorize('viewAny', AuditTemplate::class);

        return view('templates.structure.show', [
            'template' => $template,
            'tree' => $this->tree->fromTemplate($template),
        ]);
    }

    public function create(AuditTemplate $template, string $type): View
    {
        $this->authorize('update', $template);
        $this->ensureType($type);

        return view('templates.structure.form', [
            'template' => $template,
            'type' => $type,
            'entity' => null,
            ...$this->formPayload($template, $type),
        ]);
    }

    public function store(TemplateStructureRequest $request, AuditTemplate $template, string $type): RedirectResponse
    {
        $this->authorize('update', $template);
        $this->ensureType($type);

        $data = $request->validated();

        match ($type) {
            'principle' => $template->principles()->create($data),
            'section' => AuditTemplatePrinciple::findOrFail($data['principle_id'])
                ->sections()->create($this->withoutKeys($data, ['principle_id'])),
            'indicator' => AuditTemplateSection::findOrFail($data['section_id'])
                ->indicators()->create($this->withoutKeys($data, ['section_id'])),
            'parameter' => AuditTemplateIndicator::findOrFail($data['indicator_id'])
                ->parameters()->create($this->withoutKeys($data, ['indicator_id'])),
        };

        return redirect()->route('templates.structure.index', $template)
            ->with('success', 'Item struktur berhasil ditambahkan.');
    }

    public function edit(AuditTemplate $template, string $type, int $id): View
    {
        $this->authorize('update', $template);
        $this->ensureType($type);

        return view('templates.structure.form', [
            'template' => $template,
            'type' => $type,
            'entity' => $this->loadEntity($template, $type, $id),
            ...$this->formPayload($template, $type),
        ]);
    }

    public function update(TemplateStructureRequest $request, AuditTemplate $template, string $type, int $id): RedirectResponse
    {
        $this->authorize('update', $template);
        $this->ensureType($type);

        $entity = $this->loadEntity($template, $type, $id);
        $entity->update($this->withoutKeys($request->validated(), $this->immutableKeys($type)));

        return redirect()->route('templates.structure.index', $template)
            ->with('success', 'Item struktur diperbarui.');
    }

    public function destroy(AuditTemplate $template, string $type, int $id): RedirectResponse
    {
        $this->authorize('update', $template);
        $this->ensureType($type);

        $this->loadEntity($template, $type, $id)->delete();

        return redirect()->route('templates.structure.index', $template)
            ->with('success', 'Item struktur dihapus.');
    }

    public function move(Request $request, AuditTemplate $template, string $type, int $id): RedirectResponse
    {
        $this->authorize('update', $template);
        $this->ensureType($type);

        $entity = $this->loadEntity($template, $type, $id);
        $direction = (string) $request->string('direction');

        abort_if(! in_array($direction, ['up', 'down'], true), 422);

        $this->swapSortOrder($entity, $direction);

        return redirect()->route('templates.structure.index', $template)
            ->with('success', 'Urutan item struktur diperbarui.');
    }

    private function ensureType(string $type): void
    {
        abort_if(! in_array($type, self::TYPES, true), 404);
    }

    private function loadEntity(AuditTemplate $template, string $type, int $id): Model
    {
        return match ($type) {
            'principle' => AuditTemplatePrinciple::query()
                ->where('audit_template_id', $template->id)
                ->findOrFail($id),
            'section' => AuditTemplateSection::query()
                ->whereHas('principle', fn ($q) => $q->where('audit_template_id', $template->id))
                ->findOrFail($id),
            'indicator' => AuditTemplateIndicator::query()
                ->whereHas('section.principle', fn ($q) => $q->where('audit_template_id', $template->id))
                ->findOrFail($id),
            'parameter' => AuditTemplateParameter::query()
                ->whereHas('indicator.section.principle', fn ($q) => $q->where('audit_template_id', $template->id))
                ->findOrFail($id),
        };
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $keys
     * @return array<string, mixed>
     */
    private function withoutKeys(array $data, array $keys): array
    {
        return array_diff_key($data, array_flip($keys));
    }

    /**
     * @return array<int, string>
     */
    private function immutableKeys(string $type): array
    {
        return match ($type) {
            'section' => ['principle_id', 'parent_id'],
            'indicator' => ['section_id'],
            'parameter' => ['indicator_id'],
            default => [],
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function formPayload(AuditTemplate $template, string $type): array
    {
        return match ($type) {
            'section' => [
                'principles' => $template->principles()->orderBy('sort_order')->get(),
                'sections' => AuditTemplateSection::query()
                    ->whereHas('principle', fn ($q) => $q->where('audit_template_id', $template->id))
                    ->with('principle')
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->get(),
            ],
            'indicator' => [
                'sections' => AuditTemplateSection::query()
                    ->whereHas('principle', fn ($q) => $q->where('audit_template_id', $template->id))
                    ->with('principle')
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->get(),
            ],
            'parameter' => [
                'indicators' => AuditTemplateIndicator::query()
                    ->whereHas('section.principle', fn ($q) => $q->where('audit_template_id', $template->id))
                    ->with('section.principle')
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->get(),
            ],
            default => [],
        };
    }

    /**
     * @param  Builder<Model>  $query
     */
    private function swapSortOrder(Model $entity, string $direction): void
    {
        $siblings = $this->siblingQuery($entity)->orderBy('sort_order')->orderBy('id')->get();

        $position = $siblings->search(fn ($sibling): bool => $sibling->id === $entity->id);
        $target = $direction === 'up' ? $position - 1 : $position + 1;

        if ($position === false || $target < 0 || $target >= $siblings->count()) {
            return;
        }

        $current = $siblings[$position];
        $adjacent = $siblings[$target];
        $currentOrder = $current->sort_order;

        $current->update(['sort_order' => $adjacent->sort_order]);
        $adjacent->update(['sort_order' => $currentOrder]);
    }

    private function siblingQuery(Model $entity): Builder
    {
        return match (true) {
            $entity instanceof AuditTemplatePrinciple => AuditTemplatePrinciple::query()->where('audit_template_id', $entity->audit_template_id),
            $entity instanceof AuditTemplateSection => AuditTemplateSection::query()
                ->where('principle_id', $entity->principle_id)
                ->where('parent_id', $entity->parent_id),
            $entity instanceof AuditTemplateIndicator => AuditTemplateIndicator::query()->where('section_id', $entity->section_id),
            default => AuditTemplateParameter::query()->where('indicator_id', $entity->indicator_id),
        };
    }
}
