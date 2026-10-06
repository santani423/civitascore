<?php

namespace Modules\Academic\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use App\Support\Http\ListQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Academic\Models\StudentRequest;
use Modules\Academic\Resources\StudentRequestResource;
use Modules\Academic\Services\StudentRequestService;

/**
 * Bagian Akademik meninjau pengajuan mahasiswa dengan konteks lengkap
 * (mahasiswa, jenis, isi). Keputusan tetap lewat Modul ApprovalWorkflow
 * (StudentRequestService → ApprovalActionService), jadi hasilnya identik
 * dengan memutuskan dari halaman Persetujuan umum.
 */
class StudentRequestReviewController extends Controller
{
    public function __construct(private readonly StudentRequestService $requests) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', StudentRequest::class);

        $query = StudentRequest::query()
            ->where('status', '!=', 'draft')
            ->with(['student', 'academicTerm', 'attachment', 'decider', 'approvalRequest.currentStep.workflowStep'])
            ->latest('submitted_at');

        if ($search = trim((string) $request->query('search', ''))) {
            $query->whereHas('student', fn (Builder $students) => $students->where(fn (Builder $inner) => $inner
                ->where('name', 'like', "%{$search}%")
                ->orWhere('nim', 'like', "%{$search}%")));
        }

        $paginator = ListQuery::paginate(
            query: $query,
            request: $request,
            filterable: ['status', 'type'],
        );

        return ApiResponse::paginated(StudentRequestResource::collection($paginator));
    }

    public function show(StudentRequest $studentRequest): JsonResponse
    {
        $this->authorize('view', $studentRequest);

        return ApiResponse::success(new StudentRequestResource($this->loadDetail($studentRequest)));
    }

    public function approve(Request $request, StudentRequest $studentRequest): JsonResponse
    {
        $this->authorize('view', $studentRequest);
        $data = $request->validate(['note' => ['nullable', 'string', 'max:1000']]);

        $studentRequest = $this->requests->approve($studentRequest, $request->user(), $data['note'] ?? null);

        return ApiResponse::success(new StudentRequestResource($this->loadDetail($studentRequest)), 'Pengajuan disetujui.');
    }

    public function reject(Request $request, StudentRequest $studentRequest): JsonResponse
    {
        $this->authorize('view', $studentRequest);
        $data = $request->validate(['note' => ['required', 'string', 'min:5', 'max:1000']], [
            'note.required' => 'Tuliskan alasan penolakan.',
        ]);

        $studentRequest = $this->requests->reject($studentRequest, $request->user(), $data['note']);

        return ApiResponse::success(new StudentRequestResource($this->loadDetail($studentRequest)), 'Pengajuan ditolak.');
    }

    private function loadDetail(StudentRequest $studentRequest): StudentRequest
    {
        return $studentRequest->load([
            'student', 'academicTerm', 'attachment', 'decider',
            'approvalRequest.currentStep.workflowStep', 'approvalRequest.histories',
        ]);
    }
}
