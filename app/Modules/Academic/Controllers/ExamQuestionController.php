<?php

namespace Modules\Academic\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Modules\Academic\Models\Exam;
use Modules\Academic\Models\ExamQuestion;
use Modules\Academic\Requests\ApplyQuestionBankToExamRequest;
use Modules\Academic\Requests\StoreExamQuestionRequest;
use Modules\Academic\Requests\UpdateExamQuestionRequest;
use Modules\Academic\Resources\ExamQuestionResource;
use Modules\Academic\Services\ExamService;
use Modules\Academic\Services\QuestionBankService;

/** Nested under an Exam — authorization stays keyed on the parent Exam::class, no separate ExamQuestionPolicy. */
class ExamQuestionController extends Controller
{
    public function __construct(
        private readonly ExamService $exams,
        private readonly QuestionBankService $questionBank,
    ) {}

    public function index(Exam $exam): JsonResponse
    {
        $this->authorize('viewAny', Exam::class);

        $questions = $exam->questions()->with('options')->orderBy('order_index')->get();

        return ApiResponse::success(ExamQuestionResource::collection($questions));
    }

    public function store(StoreExamQuestionRequest $request, Exam $exam): JsonResponse
    {
        $this->authorize('manage', Exam::class);

        $question = $this->exams->addQuestion($exam, $request->validated());

        return ApiResponse::success(new ExamQuestionResource($question), 'Soal berhasil ditambahkan.', status: 201);
    }

    public function update(UpdateExamQuestionRequest $request, ExamQuestion $examQuestion): JsonResponse
    {
        $this->authorize('manage', Exam::class);

        $question = $this->exams->updateQuestion($examQuestion, $request->validated());

        return ApiResponse::success(new ExamQuestionResource($question), 'Soal berhasil diperbarui.');
    }

    public function destroy(ExamQuestion $examQuestion): JsonResponse
    {
        $this->authorize('manage', Exam::class);

        $this->exams->deleteQuestion($examQuestion);

        return ApiResponse::success(null, 'Soal berhasil dihapus.');
    }

    /** Menyalin soal terpilih dari bank soal ke pool ujian ini (spec §7). */
    public function applyBank(ApplyQuestionBankToExamRequest $request, Exam $exam): JsonResponse
    {
        $this->authorize('manage', Exam::class);

        $questions = $this->questionBank->applyToExam($exam, $request->validated('question_bank_item_ids'));

        return ApiResponse::success(
            ExamQuestionResource::collection($questions),
            "{$questions->count()} soal berhasil diterapkan dari bank soal.",
        );
    }
}
