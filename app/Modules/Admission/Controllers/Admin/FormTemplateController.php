<?php

namespace Modules\Admission\Controllers\Admin;

use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Admission\Models\FormField;
use Modules\Admission\Models\FormTemplate;
use Modules\Admission\Services\Configuration\FormTemplateService;
use Modules\Admission\Support\CoreFormFields;
use Modules\Admission\Support\PmbFileStorage;

/** Template formulir dinamis (FR-CFG-08, ADM-14). */
class FormTemplateController extends AdminController
{
    public function __construct(private readonly FormTemplateService $templates) {}

    public function index(Request $request): JsonResponse
    {
        return ApiResponse::success(FormTemplate::query()->withCount('fields')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderBy('code')->orderByDesc('version')->get());
    }

    public function show(FormTemplate $formTemplate): JsonResponse
    {
        return ApiResponse::success([
            ...$formTemplate->toArray(),
            'fields' => $formTemplate->fields()->orderBy('stage')->orderBy('section')->orderBy('sort_order')->get(),
            'core_fields' => CoreFormFields::FIELDS,
            'field_types' => FormTemplateService::FIELD_TYPES,
            'in_use' => \Modules\Admission\Models\WavePath::query()->where('form_template_id', $formTemplate->getKey())->exists(),
        ]);
    }

    public function preview(Request $request, FormTemplate $formTemplate): JsonResponse
    {
        $stage = $request->string('stage', 'application')->toString();

        return ApiResponse::success($this->templates->schema($formTemplate, in_array($stage, ['application', 're_registration'], true) ? $stage : 'application'));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:30', 'regex:/^[A-Za-z0-9_-]+$/'],
            'name' => ['required', 'string', 'max:150'],
            'core_field_rules' => ['nullable', 'array'],
        ]);

        return ApiResponse::success($this->templates->create($data, (string) $this->user($request)->getKey()), 'Template dibuat.', status: 201);
    }

    public function update(Request $request, FormTemplate $formTemplate): JsonResponse
    {
        $data = $request->validate(['name' => ['sometimes', 'string', 'max:150'], 'core_field_rules' => ['sometimes', 'array']]);

        return ApiResponse::success($this->templates->update($formTemplate, $data, (string) $this->user($request)->getKey()), 'Template diperbarui.');
    }

    public function storeField(Request $request, FormTemplate $formTemplate): JsonResponse
    {
        return ApiResponse::success($this->templates->saveField($formTemplate, null, $this->validateField($request, true)), 'Field ditambahkan.', status: 201);
    }

    public function updateField(Request $request, FormTemplate $formTemplate, FormField $formField): JsonResponse
    {
        abort_unless($formField->form_template_id === $formTemplate->getKey(), 404);

        return ApiResponse::success($this->templates->saveField($formTemplate, $formField, $this->validateField($request, false)), 'Field diperbarui.');
    }

    public function destroyField(FormTemplate $formTemplate, FormField $formField): JsonResponse
    {
        abort_unless($formField->form_template_id === $formTemplate->getKey(), 404);
        $this->templates->deleteField($formTemplate, $formField);

        return ApiResponse::success(null, 'Field dihapus.');
    }

    public function publish(Request $request, FormTemplate $formTemplate): JsonResponse
    {
        return ApiResponse::success($this->templates->publish($formTemplate, (string) $this->user($request)->getKey()), 'Template dipublikasikan.');
    }

    public function retire(Request $request, FormTemplate $formTemplate): JsonResponse
    {
        return ApiResponse::success($this->templates->retire($formTemplate, (string) $this->user($request)->getKey()), 'Template dipensiunkan.');
    }

    public function newVersion(Request $request, FormTemplate $formTemplate): JsonResponse
    {
        return ApiResponse::success($this->templates->newVersion($formTemplate, (string) $this->user($request)->getKey()), 'Versi baru dibuat.', status: 201);
    }

    /** @return array<string, mixed> */
    private function validateField(Request $request, bool $creating): array
    {
        $req = $creating ? 'required' : 'sometimes';

        return $request->validate([
            'section' => [$req, 'string', 'max:50'],
            'key' => [$req, 'string', 'max:60', 'regex:/^[a-z][a-z0-9_]*$/'],
            'label' => [$req, 'string', 'max:200'],
            'help_text' => ['nullable', 'string', 'max:500'],
            'placeholder' => ['nullable', 'string', 'max:200'],
            'type' => [$req, Rule::in(FormTemplateService::FIELD_TYPES)],
            'options' => ['nullable', 'array', 'max:200'],
            'options.*.value' => ['required', 'string', 'max:100'],
            'options.*.label' => ['required', 'string', 'max:200'],
            'is_required' => ['sometimes', 'boolean'],
            'validation' => ['nullable', 'array'],
            'visibility_rule' => ['nullable', 'array'],
            'is_sensitive' => ['sometimes', 'boolean'],
            'is_reportable' => ['sometimes', 'boolean'],
            'stage' => [$req, Rule::in(['application', 're_registration'])],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);
    }
}
