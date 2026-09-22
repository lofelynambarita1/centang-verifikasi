<?php

namespace App\Http\Requests;

use App\Models\AuditTemplateIndicator;
use App\Models\AuditTemplateParameter;
use App\Models\AuditTemplatePrinciple;
use App\Models\AuditTemplateSection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TemplateStructureRequest extends FormRequest
{
    private const TYPES = ['principle', 'section', 'indicator', 'parameter'];

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $type = (string) $this->route('type');

        abort_if(! in_array($type, self::TYPES, true), 404);

        $common = [
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];

        $context = $this->isCreate() ? 'required' : 'sometimes';

        return match ($type) {
            'principle' => [
                'code' => ['required', 'string', 'max:100'],
                'title' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                ...$common,
            ],
            'section' => [
                'principle_id' => [$context, 'integer', Rule::exists('audit_template_principles', 'id')->where('audit_template_id', $this->route('template')->id)],
                'parent_id' => ['nullable', 'integer'],
                'code' => ['required', 'string', 'max:100'],
                'title' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                ...$common,
            ],
            'indicator' => [
                'section_id' => [$context, 'integer'],
                'code' => ['required', 'string', 'max:100'],
                'title' => ['required', 'string'],
                'description' => ['nullable', 'string'],
                'indicator_type' => ['nullable', 'string', 'max:50'],
                ...$common,
            ],
            'parameter' => [
                'indicator_id' => [$context, 'integer'],
                'parameter_type' => ['required', Rule::in(['parameter', 'verifier'])],
                'number' => ['nullable', 'string', 'max:50'],
                'content' => ['required', 'string'],
                ...$common,
            ],
        };
    }

    /**
     * Additional ownership checks that cannot be expressed with rule strings.
     *
     * @return array<string, \Closure>
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $type = (string) $this->route('type');

            $this->validateCodeUniqueness($type, $validator);
            $this->validateSectionParentScope($type, $validator);
            $this->validateParentExists($type, $validator);
        });
    }

    private function isCreate(): bool
    {
        return $this->isMethod('POST');
    }

    private function currentEntity(string $type): ?object
    {
        if (! $this->has('id') && ! $this->route('id')) {
            return null;
        }

        $id = (int) $this->route('id');
        if ($id === 0) {
            return null;
        }

        return match ($type) {
            'principle' => AuditTemplatePrinciple::find($id),
            'section' => AuditTemplateSection::find($id),
            'indicator' => AuditTemplateIndicator::find($id),
            'parameter' => AuditTemplateParameter::find($id),
        };
    }

    private function validateCodeUniqueness(string $type, $validator): void
    {
        if ($type === 'parameter') {
            return;
        }

        $templateId = $this->route('template')->id;
        $entity = $this->currentEntity($type);
        $code = $this->input('code');
        $exists = null;

        switch ($type) {
            case 'principle':
                $exists = AuditTemplatePrinciple::query()
                    ->where('audit_template_id', $templateId)
                    ->when($entity, fn ($q) => $q->whereKeyNot($entity->id))
                    ->where('code', $code)
                    ->exists();
                break;
            case 'section':
                $principleId = $this->input('principle_id', $entity?->principle_id);
                $exists = AuditTemplateSection::query()
                    ->where('principle_id', $principleId)
                    ->when($entity, fn ($q) => $q->whereKeyNot($entity->id))
                    ->where('code', $code)
                    ->exists();
                break;
            case 'indicator':
                $sectionId = $this->input('section_id', $entity?->section_id);
                $exists = AuditTemplateIndicator::query()
                    ->where('section_id', $sectionId)
                    ->when($entity, fn ($q) => $q->whereKeyNot($entity->id))
                    ->where('code', $code)
                    ->exists();
                break;
        }

        if ($exists) {
            $validator->errors()->add('code', 'Kode sudah dipakai pada level struktur yang sama.');
        }
    }

    private function validateSectionParentScope(string $type, $validator): void
    {
        if ($type !== 'section' || ! $this->isCreate() || ! $this->filled('parent_id')) {
            return;
        }

        $parent = AuditTemplateSection::find($this->integer('parent_id'));

        if ($parent === null || (int) $parent->principle_id !== (int) $this->integer('principle_id')) {
            $validator->errors()->add('parent_id', 'Section induk harus berada pada prinsip yang sama.');
        }
    }

    private function validateParentExists(string $type, $validator): void
    {
        if ($type === 'principle') {
            return;
        }

        $templateId = (int) $this->route('template')->id;

        $attributes = match ($type) {
            'section' => [
                'principle_id' => fn (): bool => AuditTemplatePrinciple::query()
                    ->where('audit_template_id', $templateId)
                    ->whereKey($this->integer('principle_id'))
                    ->exists(),
            ],
            'indicator' => [
                'section_id' => fn (): bool => AuditTemplateSection::query()
                    ->whereKey($this->integer('section_id'))
                    ->whereHas('principle', fn ($q) => $q->where('audit_template_id', $templateId))
                    ->exists(),
            ],
            'parameter' => [
                'indicator_id' => fn (): bool => AuditTemplateIndicator::query()
                    ->whereKey($this->integer('indicator_id'))
                    ->whereHas('section.principle', fn ($q) => $q->where('audit_template_id', $templateId))
                    ->exists(),
            ],
            default => [],
        };

        foreach ($attributes as $attribute => $callback) {
            if (! $this->filled($attribute)) {
                continue;
            }

            if (! $callback()) {
                $validator->errors()->add($attribute, 'Item induk harus berada dalam lingkup template ini.');
            }
        }
    }
}
