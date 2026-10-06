<?php

namespace Modules\Academic\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Academic\Controllers\Concerns\ResolvesCurrentStudent;
use Modules\Academic\Models\StudentRequest;
use Modules\Academic\Requests\StoreStudentRequestRequest;
use Modules\Academic\Resources\StudentRequestResource;
use Modules\Academic\Services\StudentRequestService;

/**
 * Portal Mahasiswa — pengajuan akademik milik sendiri (cuti, aktif kembali,
 * perubahan data, surat, lainnya). Mahasiswa tidak pernah bisa mengubah
 * status persetujuan — hanya membuat, mengubah draft, mengirim, membatalkan.
 */
class StudentRequestController extends Controller
{
    use ResolvesCurrentStudent;

    public function __construct(private readonly StudentRequestService $requests) {}

    public function index(Request $request): JsonResponse
    {
        $student = $this->currentStudent($request);

        $requests = StudentRequest::query()
            ->where('student_id', $student->id)
            ->with(['academicTerm', 'attachment', 'decider', 'approvalRequest.currentStep.workflowStep'])
            ->latest()
            ->paginate(min(max((int) $request->query('per_page', 20), 1), 100));

        return ApiResponse::paginated(StudentRequestResource::collection($requests));
    }

    public function options(): JsonResponse
    {
        return ApiResponse::success([
            'letter_types' => collect(StudentRequestService::LETTER_TYPES)
                ->map(fn (string $label, string $value) => ['value' => $value, 'label' => $label])
                ->values(),
            'data_change_fields' => StudentRequestService::DATA_CHANGE_FIELDS,
        ]);
    }

    public function show(Request $request, StudentRequest $studentRequest): JsonResponse
    {
        $this->currentStudent($request);
        $this->authorize('viewOwn', $studentRequest);

        return ApiResponse::success(new StudentRequestResource($this->loadDetail($studentRequest)));
    }

    public function store(StoreStudentRequestRequest $request): JsonResponse
    {
        $studentRequest = $this->requests->create($this->currentStudent($request), $request->validated(), $request->user());

        return ApiResponse::success(
            new StudentRequestResource($this->loadDetail($studentRequest)),
            $studentRequest->submitted_at !== null ? 'Pengajuan berhasil dikirim.' : 'Draft pengajuan disimpan.',
            status: 201,
        );
    }

    public function update(StoreStudentRequestRequest $request, StudentRequest $studentRequest): JsonResponse
    {
        $this->currentStudent($request);
        $this->authorize('viewOwn', $studentRequest);

        $studentRequest = $this->requests->update($studentRequest, $request->validated(), $request->user());

        if ($request->boolean('submit')) {
            $studentRequest = $this->requests->submit($studentRequest, $request->user());
        }

        return ApiResponse::success(new StudentRequestResource($this->loadDetail($studentRequest)), 'Pengajuan diperbarui.');
    }

    public function submit(Request $request, StudentRequest $studentRequest): JsonResponse
    {
        $this->currentStudent($request);
        $this->authorize('viewOwn', $studentRequest);

        $studentRequest = $this->requests->submit($studentRequest, $request->user());

        return ApiResponse::success(new StudentRequestResource($this->loadDetail($studentRequest)), 'Pengajuan berhasil dikirim.');
    }

    public function cancel(Request $request, StudentRequest $studentRequest): JsonResponse
    {
        $this->currentStudent($request);
        $this->authorize('viewOwn', $studentRequest);

        $studentRequest = $this->requests->cancel($studentRequest);

        return ApiResponse::success(new StudentRequestResource($this->loadDetail($studentRequest)), 'Pengajuan dibatalkan.');
    }

    private function loadDetail(StudentRequest $studentRequest): StudentRequest
    {
        return $studentRequest->load([
            'academicTerm', 'attachment', 'decider',
            'approvalRequest.currentStep.workflowStep', 'approvalRequest.histories',
        ]);
    }
}
