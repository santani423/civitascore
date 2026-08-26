<?php

namespace Modules\Academic\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use App\Support\Http\ListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Academic\Models\QuestionBankItem;
use Modules\Academic\Requests\StoreQuestionBankItemRequest;
use Modules\Academic\Requests\UpdateQuestionBankItemRequest;
use Modules\Academic\Resources\QuestionBankItemResource;
use Modules\Academic\Services\QuestionBankService;

class QuestionBankController extends Controller
{
    public function __construct(private readonly QuestionBankService $questionBank) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', QuestionBankItem::class);

        // 'options' dimuat di sini juga (bukan hanya di show()) karena
        // QuestionBankPage membuka modal Ubah langsung dari baris daftar
        // ini, tanpa fetch detail terpisah — tanpa ini formulir edit tidak
        // punya opsi jawaban untuk diisi ulang.
        $query = QuestionBankItem::query()->with(['course', 'options'])->withCount('examQuestions');

        $paginator = ListQuery::paginate(
            query: $query->latest(),
            request: $request,
            searchable: ['question_text'],
            filterable: ['course_id'],
            sortable: ['created_at'],
        );

        return ApiResponse::paginated(QuestionBankItemResource::collection($paginator));
    }

    public function store(StoreQuestionBankItemRequest $request): JsonResponse
    {
        $this->authorize('manage', QuestionBankItem::class);

        $item = $this->questionBank->createItem($request->validated());

        return ApiResponse::success(
            new QuestionBankItemResource($item->load('course')),
            'Soal berhasil ditambahkan ke bank soal.',
            status: 201,
        );
    }

    public function show(QuestionBankItem $questionBankItem): JsonResponse
    {
        $this->authorize('viewAny', QuestionBankItem::class);

        return ApiResponse::success(new QuestionBankItemResource($questionBankItem->load(['course', 'options'])));
    }

    public function update(UpdateQuestionBankItemRequest $request, QuestionBankItem $questionBankItem): JsonResponse
    {
        $this->authorize('manage', QuestionBankItem::class);

        $item = $this->questionBank->updateItem($questionBankItem, $request->validated());

        return ApiResponse::success(
            new QuestionBankItemResource($item->load('course')),
            'Soal bank berhasil diperbarui.',
        );
    }

    public function destroy(QuestionBankItem $questionBankItem): JsonResponse
    {
        $this->authorize('delete', QuestionBankItem::class);

        $this->questionBank->deleteItem($questionBankItem);

        return ApiResponse::success(null, 'Soal berhasil dihapus dari bank soal.');
    }
}
