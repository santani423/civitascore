<?php

namespace Modules\Academic\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Academic\Models\KrsSubmission;
use Modules\Academic\Requests\DecideKrsSubmissionRequest;
use Modules\Academic\Resources\KrsSubmissionResource;
use Modules\Academic\Services\KrsPlanService;

/**
 * Persetujuan KRS oleh dosen wali (mahasiswa perwaliannya) dan Bagian
 * Akademik (semua mahasiswa) — lihat KrsSubmissionPolicy.
 */
class KrsSubmissionController extends Controller
{
    public function __construct(private readonly KrsPlanService $krs) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', KrsSubmission::class);

        $filters = $request->validate([
            'status' => ['nullable', 'string', 'in:all,draft,submitted,approved,rejected'],
            'academic_term_id' => ['nullable', 'string', 'max:26'],
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        return ApiResponse::paginated(KrsSubmissionResource::collection($this->krs->approvalQueue($request->user(), $filters)));
    }

    public function show(KrsSubmission $krsSubmission): JsonResponse
    {
        $this->authorize('view', $krsSubmission);

        return ApiResponse::success(new KrsSubmissionResource($this->loadDetail($krsSubmission)));
    }

    public function approve(DecideKrsSubmissionRequest $request, KrsSubmission $krsSubmission): JsonResponse
    {
        $this->authorize('decide', $krsSubmission);

        $submission = $this->krs->approve($krsSubmission, $request->user(), $request->validated('note'));

        return ApiResponse::success(new KrsSubmissionResource($this->loadDetail($submission)), 'KRS disetujui.');
    }

    public function reject(DecideKrsSubmissionRequest $request, KrsSubmission $krsSubmission): JsonResponse
    {
        $this->authorize('decide', $krsSubmission);

        $request->validate(['note' => ['required', 'string', 'min:5', 'max:1000']], [
            'note.required' => 'Tuliskan alasan penolakan supaya mahasiswa dapat memperbaiki KRS-nya.',
            'note.min' => 'Alasan penolakan minimal 5 karakter.',
        ]);

        $submission = $this->krs->reject($krsSubmission, $request->user(), (string) $request->validated('note'));

        return ApiResponse::success(new KrsSubmissionResource($this->loadDetail($submission)), 'KRS ditolak dan dikembalikan ke mahasiswa.');
    }

    private function loadDetail(KrsSubmission $submission): KrsSubmission
    {
        return $submission->load([
            'student.studyProgram', 'student.academicAdvisor', 'academicTerm', 'decider',
            'items.classSection.course', 'items.classSection.lecturer', 'items.classSection.schedules',
        ]);
    }
}
