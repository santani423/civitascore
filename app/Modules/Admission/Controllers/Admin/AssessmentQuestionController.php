<?php

namespace Modules\Admission\Controllers\Admin;

use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Admission\Enums\AssessmentQuestionType;
use Modules\Admission\Models\AssessmentItem;
use Modules\Admission\Models\AssessmentType;
use Modules\Admission\Models\AssessmentVersion;
use Modules\Admission\Services\Assessment\AssessmentQuestionBankService;

/** Bank soal One Atma Talent (docs/one-atma-talent/03 §5). Butir dikelola per versi draft dan dapat disalin antarversi. */
class AssessmentQuestionController extends AdminController
{
    public function __construct(private readonly AssessmentQuestionBankService $bank) {}

    /** Daftar butir: pencarian, filter dimensi/status/versi/jenis, pengurutan, dan paginasi. */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'type_id' => ['nullable', 'string', 'size:26'],
            'version_id' => ['nullable', 'string', 'size:26'],
            'dimension' => ['nullable', 'string', 'max:10'],
            'status' => ['nullable', Rule::in(['draft', 'approved'])],
            'search' => ['nullable', 'string', 'max:100'],
            'sort' => ['nullable', Rule::in(['code', 'stem', 'created_at', 'sort_order'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);

        $type = ! empty($filters['type_id']) ? AssessmentType::query()->whereKey($filters['type_id'])->first() : null;
        $query = $this->bank->query($type, $filters)->withExists('responses as in_use');

        return $this->paginate($query, $request, fn (AssessmentItem $item) => $this->row($item));
    }

    public function show(AssessmentItem $item): JsonResponse
    {
        $item->load(['dimension:id,code,name', 'version:id,version_no,title,status,assessment_type_id']);

        return ApiResponse::success([...$this->row($item), 'in_use' => $item->responses()->exists(), 'preview' => $this->bank->preview($item)]);
    }

    public function store(Request $request, AssessmentVersion $version): JsonResponse
    {
        $data = $request->validate($this->rules(required: true));
        $item = $this->bank->store($version, $data)->load(['dimension:id,code,name', 'version:id,version_no,title,status,assessment_type_id']);

        return ApiResponse::success($this->row($item), 'Pertanyaan ditambahkan.', status: 201);
    }

    public function update(Request $request, AssessmentItem $item): JsonResponse
    {
        $data = $request->validate($this->rules(required: false));
        $item = $this->bank->update($item, $data)->load(['dimension:id,code,name', 'version:id,version_no,title,status,assessment_type_id']);

        return ApiResponse::success($this->row($item), 'Pertanyaan diperbarui.');
    }

    public function destroy(AssessmentItem $item): JsonResponse
    {
        $this->bank->delete($item);

        return response()->json(null, 204);
    }

    /** Salin butir ke versi draft lain untuk jenis asesmen yang sama. */
    public function copy(Request $request, AssessmentItem $item): JsonResponse
    {
        $data = $request->validate(['version_id' => ['required', 'string', 'size:26']]);
        $target = AssessmentVersion::query()->whereKey($data['version_id'])->firstOrFail();
        $copy = $this->bank->copy($item, $target)->load(['dimension:id,code,name', 'version:id,version_no,title,status,assessment_type_id']);

        return ApiResponse::success($this->row($copy), 'Pertanyaan disalin ke versi draft.', status: 201);
    }

    public function preview(AssessmentItem $item): JsonResponse
    {
        return ApiResponse::success($this->bank->preview($item));
    }

    /** @return array<string, mixed> */
    private function rules(bool $required): array
    {
        $p = $required ? 'required' : 'sometimes';

        return [
            'dimension_id' => [$p, 'string', 'size:26'],
            'code' => ['nullable', 'string', 'max:30'],
            'question_type' => ['sometimes', Rule::enum(AssessmentQuestionType::class)],
            'stem' => [$p, 'string', 'min:5', 'max:1000'],
            'is_reversed' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ];
    }

    /**
     * Baris bank soal untuk staf. Arah butir dan dimensi ditampilkan karena ini ruang penyusunan
     * instrumen; peserta tidak pernah menerima bentuk ini (lihat AssessmentSessionService::payload).
     *
     * @return array<string, mixed>
     */
    private function row(AssessmentItem $item): array
    {
        return [
            'id' => $item->getKey(),
            'code' => $item->code,
            'stem' => $item->stem,
            'question_type' => $item->question_type->value,
            'status' => $item->status->value,
            'is_reversed' => $item->is_reversed,
            'sort_order' => $item->sort_order,
            'source_item_id' => $item->source_item_id,
            'in_use' => (bool) ($item->in_use ?? false),
            'dimension' => $item->relationLoaded('dimension') ? ['id' => $item->dimension->getKey(), 'code' => $item->dimension->code, 'name' => $item->dimension->name] : null,
            'version' => $item->relationLoaded('version') ? [
                'id' => $item->version->getKey(), 'version_no' => $item->version->version_no, 'title' => $item->version->title,
                'status' => $item->version->status->value, 'assessment_type_id' => $item->version->assessment_type_id,
            ] : null,
            'editable' => $item->relationLoaded('version') ? $item->version->status->isEditable() && ! ($item->in_use ?? false) : null,
            'created_at' => $item->created_at?->toIso8601String(),
        ];
    }
}
