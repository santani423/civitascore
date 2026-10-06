<?php

namespace Modules\Academic\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Academic\Controllers\Concerns\ResolvesCurrentStudent;
use Modules\Academic\Enums\KrsItemStatus;
use Modules\Academic\Models\AcademicTerm;
use Modules\Academic\Models\KrsItem;
use Modules\Academic\Models\StudentRequest;
use Modules\Academic\Services\StudentAcademicService;
use Modules\Academic\Services\StudentDocumentService;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Portal Mahasiswa — unduhan dokumen akademik milik sendiri (PDF).
 */
class StudentDocumentController extends Controller
{
    use ResolvesCurrentStudent;

    public function __construct(
        private readonly StudentDocumentService $documents,
        private readonly StudentAcademicService $academics,
    ) {}

    public function index(Request $request): JsonResponse
    {
        return ApiResponse::success($this->documents->available($this->currentStudent($request)));
    }

    public function krs(Request $request): Response
    {
        $student = $this->currentStudent($request);
        $request->validate(['academic_term_id' => ['nullable', 'string', 'max:26']]);

        $termId = $request->query('academic_term_id');
        $term = $termId
            ? AcademicTerm::query()->find($termId)
            : $this->academics->currentTerm();

        if ($term === null) {
            throw new NotFoundHttpException;
        }

        return $this->documents->krsPdf($student, $term);
    }

    public function khs(Request $request, AcademicTerm $academicTerm): Response
    {
        $student = $this->currentStudent($request);

        // Semester bersama milik universitas — yang dicek adalah mahasiswa ini
        // benar-benar punya KRS terdaftar di semester itu.
        $hasEnrollment = KrsItem::query()
            ->where('student_id', $student->id)
            ->where('academic_term_id', $academicTerm->id)
            ->where('status', KrsItemStatus::Enrolled)
            ->exists();

        if (! $hasEnrollment) {
            throw new NotFoundHttpException;
        }

        return $this->documents->khsPdf($student, $academicTerm);
    }

    public function transcript(Request $request): Response
    {
        return $this->documents->transcriptPdf($this->currentStudent($request));
    }

    public function letter(Request $request, StudentRequest $studentRequest): Response
    {
        $this->currentStudent($request);
        $this->authorize('viewOwn', $studentRequest);

        return $this->documents->letterPdf($studentRequest);
    }
}
