<?php

namespace Modules\HumanResource\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Http\ApiResponse;
use App\Support\Http\Exceptions\ConflictException;
use App\Support\Http\ListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Academic\Models\Employee;
use Modules\HumanResource\Models\HrRequest;
use Modules\HumanResource\Models\LeaveRequest;
use Modules\HumanResource\Requests\StoreLeaveRequest;
use Modules\HumanResource\Resources\LeaveRequestResource;
use Modules\HumanResource\Services\HrRequestService;
use Modules\HumanResource\Services\LeaveService;

/**
 * Cuti & izin (sisi SDM). Keputusan approve/reject diteruskan ke
 * HrRequestService → ApprovalWorkflow, jadi tercatat sama persis dengan
 * persetujuan dari halaman Pengajuan/Persetujuan.
 */
class LeaveRequestController extends Controller
{
    private const RELATIONS = ['employee', 'attachmentFile', 'hrRequest', 'requester', 'approver', 'rejecter'];

    public function __construct(
        private readonly LeaveService $leaves,
        private readonly HrRequestService $requests,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', LeaveRequest::class);

        $paginator = ListQuery::paginate(
            query: LeaveRequest::query()->with(self::RELATIONS)
                ->when($request->filled('date_from'), fn ($query) => $query->whereDate('end_date', '>=', (string) $request->query('date_from')))
                ->when($request->filled('date_to'), fn ($query) => $query->whereDate('start_date', '<=', (string) $request->query('date_to')))
                ->when(! $request->filled('sort'), fn ($query) => $query->latest()),
            request: $request,
            searchable: ['reason'],
            filterable: ['employee_id', 'leave_type', 'status'],
            sortable: ['start_date', 'created_at', 'days'],
        );

        return ApiResponse::paginated(LeaveRequestResource::collection($paginator));
    }

    public function show(LeaveRequest $leaveRequest): JsonResponse
    {
        $this->authorize('view', $leaveRequest);

        return ApiResponse::success(new LeaveRequestResource($leaveRequest->load(self::RELATIONS)));
    }

    /**
     * SDM mencatat cuti atas nama pegawai (mis. pegawai tanpa akun login).
     */
    public function store(StoreLeaveRequest $request): JsonResponse
    {
        $this->authorize('create', LeaveRequest::class);
        $data = $request->validated();
        $employee = Employee::query()->whereKey($data['employee_id'])->firstOrFail();
        $submit = (bool) ($data['submit'] ?? true);
        unset($data['employee_id'], $data['submit']);

        $leave = $this->leaves->create($employee, $data, $this->actor($request), $submit);

        return ApiResponse::success(new LeaveRequestResource($leave->load(self::RELATIONS)), $submit ? 'Pengajuan cuti dikirim.' : 'Draft cuti disimpan.', status: 201);
    }

    public function submit(Request $request, LeaveRequest $leaveRequest): JsonResponse
    {
        $this->authorize('update', $leaveRequest);

        $leave = $this->leaves->submit($leaveRequest, $this->actor($request));

        return ApiResponse::success(new LeaveRequestResource($leave->load(self::RELATIONS)), 'Pengajuan cuti dikirim.');
    }

    public function approve(Request $request, LeaveRequest $leaveRequest): JsonResponse
    {
        $this->authorize('approve', $leaveRequest);
        $data = $request->validate(['note' => ['nullable', 'string', 'max:1000']]);

        $this->requests->approve($this->hrRequestOf($leaveRequest), $this->actor($request), $data['note'] ?? null);

        return ApiResponse::success(new LeaveRequestResource($leaveRequest->refresh()->load(self::RELATIONS)), 'Keputusan persetujuan disimpan.');
    }

    public function reject(Request $request, LeaveRequest $leaveRequest): JsonResponse
    {
        $this->authorize('reject', $leaveRequest);
        $data = $request->validate(['note' => ['required', 'string', 'max:1000']], ['note.required' => 'Alasan penolakan wajib diisi.']);

        $this->requests->reject($this->hrRequestOf($leaveRequest), $this->actor($request), $data['note']);

        return ApiResponse::success(new LeaveRequestResource($leaveRequest->refresh()->load(self::RELATIONS)), 'Pengajuan cuti ditolak.');
    }

    public function cancel(LeaveRequest $leaveRequest): JsonResponse
    {
        $this->authorize('cancel', $leaveRequest);

        $leave = $this->leaves->cancel($leaveRequest);

        return ApiResponse::success(new LeaveRequestResource($leave->load(self::RELATIONS)), 'Pengajuan cuti dibatalkan.');
    }

    public function quota(Employee $employee): JsonResponse
    {
        $this->authorize('viewAny', LeaveRequest::class);

        return ApiResponse::success($this->leaves->annualQuota($employee));
    }

    private function hrRequestOf(LeaveRequest $leaveRequest): HrRequest
    {
        return $leaveRequest->hrRequest ?? throw new ConflictException('Cuti ini belum diajukan untuk persetujuan.');
    }

    private function actor(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}
