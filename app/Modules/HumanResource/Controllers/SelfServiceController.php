<?php

namespace Modules\HumanResource\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Academic\Models\Employee;
use Modules\HumanResource\Enums\HrRequestType;
use Modules\HumanResource\Models\HrRequest;
use Modules\HumanResource\Models\LeaveRequest;
use Modules\HumanResource\Requests\StoreHrRequestRequest;
use Modules\HumanResource\Requests\StoreLeaveRequest;
use Modules\HumanResource\Resources\HrEmployeeDetailResource;
use Modules\HumanResource\Resources\HrRequestResource;
use Modules\HumanResource\Resources\LeaveRequestResource;
use Modules\HumanResource\Services\HrRequestService;
use Modules\HumanResource\Services\LeaveService;

/**
 * Layanan mandiri pegawai/dosen (bukan halaman SDM): profil sendiri,
 * pengajuan cuti, dan pengajuan SDM lain. Semua query dibatasi ke baris
 * pegawai yang tertaut ke akun yang login (employees.user_id) — tidak ada
 * parameter employee_id yang diterima dari klien.
 */
class SelfServiceController extends Controller
{
    public function __construct(
        private readonly LeaveService $leaves,
        private readonly HrRequestService $requests,
    ) {}

    public function profile(Request $request): JsonResponse
    {
        $employee = $this->ownEmployee($request);

        return ApiResponse::success([
            'employee' => new HrEmployeeDetailResource($employee->load(['lecturer', 'faculty', 'studyProgram', 'workUnit', 'rank'])),
            'leave_quota' => $this->leaves->annualQuota($employee),
        ]);
    }

    public function leaveRequests(Request $request): JsonResponse
    {
        $employee = $this->ownEmployee($request);

        $leaves = $employee->leaveRequests()->with(['attachmentFile', 'approver', 'rejecter', 'hrRequest'])->latest()->limit(100)->get();

        return ApiResponse::success(LeaveRequestResource::collection($leaves));
    }

    public function storeLeaveRequest(StoreLeaveRequest $request): JsonResponse
    {
        $employee = $this->ownEmployee($request);
        $data = $request->validated();
        $submit = (bool) ($data['submit'] ?? true);
        unset($data['submit']);

        $leave = $this->leaves->create($employee, $data, $this->actor($request), $submit);

        return ApiResponse::success(new LeaveRequestResource($leave->load(['attachmentFile', 'hrRequest'])), $submit ? 'Pengajuan cuti dikirim.' : 'Draft cuti disimpan.', status: 201);
    }

    public function submitLeaveRequest(Request $request, LeaveRequest $leaveRequest): JsonResponse
    {
        $this->assertOwns($request, $leaveRequest->employee_id);

        $leave = $this->leaves->submit($leaveRequest, $this->actor($request));

        return ApiResponse::success(new LeaveRequestResource($leave->load(['attachmentFile', 'hrRequest'])), 'Pengajuan cuti dikirim.');
    }

    public function cancelLeaveRequest(Request $request, LeaveRequest $leaveRequest): JsonResponse
    {
        $this->assertOwns($request, $leaveRequest->employee_id);

        $leave = $this->leaves->cancel($leaveRequest);

        return ApiResponse::success(new LeaveRequestResource($leave->load(['attachmentFile', 'hrRequest'])), 'Pengajuan cuti dibatalkan.');
    }

    public function requests(Request $request): JsonResponse
    {
        $employee = $this->ownEmployee($request);

        $requests = HrRequest::query()
            ->where('employee_id', $employee->id)
            ->with(['attachmentFile', 'leaveRequest', 'approver', 'rejecter'])
            ->latest()
            ->limit(100)
            ->get();

        return ApiResponse::success(HrRequestResource::collection($requests));
    }

    public function storeRequest(StoreHrRequestRequest $request): JsonResponse
    {
        $employee = $this->ownEmployee($request);
        $data = $request->validated();

        $hrRequest = $this->requests->submit($employee, HrRequestType::from($data['type']), $data, $this->actor($request));

        return ApiResponse::success(new HrRequestResource($hrRequest->load(['attachmentFile'])), 'Pengajuan berhasil dikirim.', status: 201);
    }

    public function cancelRequest(Request $request, HrRequest $hrRequest): JsonResponse
    {
        $this->assertOwns($request, $hrRequest->employee_id);

        $hrRequest = $this->requests->cancel($hrRequest);

        return ApiResponse::success(new HrRequestResource($hrRequest), 'Pengajuan dibatalkan.');
    }

    private function ownEmployee(Request $request): Employee
    {
        $employee = Employee::query()->where('user_id', $request->user()?->id)->first();

        abort_if($employee === null, 404, 'Akun Anda belum tertaut ke data pegawai. Hubungi Bagian SDM.');

        return $employee;
    }

    /**
     * Record milik pegawai lain dibaca sebagai "tidak ada" (404), bukan
     * 403 — tidak membocorkan keberadaannya.
     */
    private function assertOwns(Request $request, string $employeeId): void
    {
        abort_unless($this->ownEmployee($request)->id === $employeeId, 404);
    }

    private function actor(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}
