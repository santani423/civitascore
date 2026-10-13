<?php

namespace Modules\Admission\Controllers\Admin;

use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Admission\Enums\AllocationMethod;
use Modules\Admission\Enums\ComponentSource;
use Modules\Admission\Enums\NormalizationMethod;
use Modules\Admission\Enums\ScoreType;
use Modules\Admission\Models\SelectionComponent;
use Modules\Admission\Models\SelectionScheme;
use Modules\Admission\Services\Configuration\SelectionSchemeService;

/** Skema seleksi, komponen, kunci, simulasi (FR-SEL-01, ADM-17). */
class SelectionSchemeController extends AdminController
{
    public function __construct(private readonly SelectionSchemeService $schemes) {}

    public function index(Request $request): JsonResponse
    {
        return ApiResponse::success(SelectionScheme::query()->withCount('components')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderBy('code')->orderByDesc('version')->get());
    }

    public function show(SelectionScheme $selectionScheme): JsonResponse
    {
        $components = $selectionScheme->components()->orderBy('sort_order')->get();

        return ApiResponse::success([
            ...$selectionScheme->toArray(),
            'components' => $components,
            'lock_errors' => $selectionScheme->status->value === 'draft' ? $this->schemes->lockErrors($selectionScheme, $components->all()) : [],
            'in_use' => \Modules\Admission\Models\WavePath::query()->where('selection_scheme_id', $selectionScheme->getKey())->exists(),
            'enums' => ['sources' => ComponentSource::values(), 'score_types' => ScoreType::values(), 'normalizations' => NormalizationMethod::values(), 'allocation_methods' => AllocationMethod::values()],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:30', 'regex:/^[A-Za-z0-9_-]+$/'],
            ...$this->schemeRules('required'),
        ]);

        return ApiResponse::success($this->schemes->create($data, (string) $this->user($request)->getKey()), 'Skema dibuat.', status: 201);
    }

    public function update(Request $request, SelectionScheme $selectionScheme): JsonResponse
    {
        return ApiResponse::success($this->schemes->update($selectionScheme, $request->validate($this->schemeRules('sometimes')), (string) $this->user($request)->getKey()), 'Skema diperbarui.');
    }

    public function storeComponent(Request $request, SelectionScheme $selectionScheme): JsonResponse
    {
        return ApiResponse::success($this->schemes->saveComponent($selectionScheme, null, $this->validateComponent($request, true)), 'Komponen ditambahkan.', status: 201);
    }

    public function updateComponent(Request $request, SelectionScheme $selectionScheme, SelectionComponent $selectionComponent): JsonResponse
    {
        abort_unless($selectionComponent->selection_scheme_id === $selectionScheme->getKey(), 404);

        return ApiResponse::success($this->schemes->saveComponent($selectionScheme, $selectionComponent, $this->validateComponent($request, false)), 'Komponen diperbarui.');
    }

    public function destroyComponent(SelectionScheme $selectionScheme, SelectionComponent $selectionComponent): JsonResponse
    {
        abort_unless($selectionComponent->selection_scheme_id === $selectionScheme->getKey(), 404);
        $this->schemes->deleteComponent($selectionScheme, $selectionComponent);

        return ApiResponse::success(null, 'Komponen dihapus.');
    }

    public function lock(Request $request, SelectionScheme $selectionScheme): JsonResponse
    {
        return ApiResponse::success($this->schemes->lock($selectionScheme, (string) $this->user($request)->getKey()), 'Skema dikunci.');
    }

    public function newVersion(Request $request, SelectionScheme $selectionScheme): JsonResponse
    {
        return ApiResponse::success($this->schemes->newVersion($selectionScheme, (string) $this->user($request)->getKey()), 'Versi baru dibuat.', status: 201);
    }

    public function simulate(Request $request, SelectionScheme $selectionScheme): JsonResponse
    {
        $data = $request->validate([
            'applicants' => ['required', 'array', 'min:1', 'max:500'],
            'applicants.*.id' => ['nullable', 'string', 'max:50'],
            'applicants.*.scores' => ['required', 'array'],
            'applicants.*.choices' => ['required', 'array', 'min:1', 'max:5'],
            'applicants.*.choices.*.quota' => ['required', 'string', 'max:50'],
            'quotas' => ['required', 'array', 'min:1', 'max:200'],
            'quotas.*.capacity' => ['required', 'integer', 'min:0'],
            'quotas.*.waitlist_capacity' => ['nullable', 'integer', 'min:0'],
            'quotas.*.min_final_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        return ApiResponse::success($this->schemes->simulate($selectionScheme, $data['applicants'], $data['quotas']));
    }

    /** @return array<string, mixed> */
    private function schemeRules(string $req): array
    {
        return [
            'name' => [$req, 'string', 'max:150'],
            'passing_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'allocation_method' => ['sometimes', Rule::in(AllocationMethod::values())],
            'tie_break_rules' => ['sometimes', 'array'],
            'tie_break_rules.use_verified_at' => ['sometimes', 'boolean'],
            'rounding_scale' => ['sometimes', 'integer', 'min:0', 'max:4'],
        ];
    }

    /** @return array<string, mixed> */
    private function validateComponent(Request $request, bool $creating): array
    {
        $req = $creating ? 'required' : 'sometimes';

        return $request->validate([
            'code' => [$req, 'string', 'max:30', 'regex:/^[A-Za-z0-9_-]+$/'],
            'name' => [$req, 'string', 'max:150'],
            'source' => [$req, Rule::in(ComponentSource::values())],
            'score_type' => [$req, Rule::in(ScoreType::values())],
            'raw_min' => ['nullable', 'numeric'],
            'raw_max' => ['nullable', 'numeric'],
            'weight' => [$req, 'numeric', 'min:0', 'max:100'],
            'minimum_normalized' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_mandatory' => ['sometimes', 'boolean'],
            'tie_break_priority' => ['nullable', 'integer', 'min:1', 'max:20'],
            'normalization' => ['sometimes', Rule::in(NormalizationMethod::values())],
            'rubric' => ['nullable', 'array'],
            'config' => ['nullable', 'array'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);
    }
}
