<?php

namespace Modules\HumanResource\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Http\ApiResponse;
use App\Support\Http\ListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Academic\Models\Employee;
use Modules\HumanResource\Enums\HrRequestStatus;
use Modules\HumanResource\Enums\HrRequestType;
use Modules\HumanResource\Models\HrRequest;
use Modules\HumanResource\Requests\StoreHrRequestRequest;
use Modules\HumanResource\Resources\HrRequestResource;
use Modules\HumanResource\Services\HrRequestService;

/**
 * Halaman terpusat Pengajuan SDM & Persetujuan.
 */
class HrRequestController extends Controller
{
    private const RELATIONS = [
        'employee', 'attachmentFile', 'leaveRequest', 'requester', 'approver', 'rejecter', 'processor',
        'approvalRequest.currentStep.workflowStep.approverRole',
    ];

    public function __construct(private readonly HrRequestService $requests) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', HrRequest::class);

        $paginator = ListQuery::paginate(
            query: HrRequest::query()->with(self::RELATIONS)
                ->when($request->boolean('needs_processing'), fn ($query) => $query
                    ->where('status', HrRequestStatus::Approved)
                    ->whereIn('type', [HrRequestType::Transfer, HrRequestType::Promotion, HrRequestType::Document])
                    ->whereNull('processed_at'))
                ->when(! $request->filled('sort'), fn ($query) => $query->latest()),
            request: $request,
            searchable: ['title', 'description'],
            filterable: ['employee_id', 'type', 'status'],
            sortable: ['created_at', 'approved_at'],
        );

        return ApiResponse::paginated(HrRequestResource::collection($paginator));
    }

    /**
     * Kotak Persetujuan: pengajuan menunggu yang langkahnya saat ini
     * memang bisa diputuskan oleh user yang login (dicek lewat
     * ApprovalRequestStepPolicy yang sama dengan halaman Persetujuan umum).
     */
    public function approvals(Request $request): JsonResponse
    {
        abort_unless(
            $request->user()?->hasPermissionTo('hr_requests.approve') || $request->user()?->hasPermissionTo('hr_leave.approve'),
            403,
        );

        /** @var User $user */
        $user = $request->user();

        $actionable = HrRequest::query()
            ->with(self::RELATIONS)
            ->where('status', HrRequestStatus::Pending)
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->query('type')))
            ->oldest()
            ->get()
            ->filter(fn (HrRequest $hrRequest): bool => $this->requests->canAct($hrRequest, $user)
                && $user->can('approve', $hrRequest))
            ->values();

        return ApiResponse::success(HrRequestResource::collection($actionable), meta: ['total' => $actionable->count()]);
    }

    public function show(HrRequest $hrRequest): JsonResponse
    {
        $this->authorize('view', $hrRequest);

        return ApiResponse::success(new HrRequestResource($hrRequest->load([...self::RELATIONS, 'approvalRequest.histories.actor'])));
    }

    /**
     * SDM membuat pengajuan atas nama pegawai.
     */
    public function store(StoreHrRequestRequest $request): JsonResponse
    {
        $this->authorize('create', HrRequest::class);
        $data = $request->validated();
        $employee = Employee::query()->whereKey($data['employee_id'])->firstOrFail();

        $hrRequest = $this->requests->submit($employee, HrRequestType::from($data['type']), $data, $this->actor($request));

        return ApiResponse::success(new HrRequestResource($hrRequest->load(self::RELATIONS)), 'Pengajuan berhasil dibuat.', status: 201);
    }

    public function approve(Request $request, HrRequest $hrRequest): JsonResponse
    {
        $this->authorize('approve', $hrRequest);
        $data = $request->validate(['note' => ['nullable', 'string', 'max:1000']]);

        $hrRequest = $this->requests->approve($hrRequest, $this->actor($request), $data['note'] ?? null);

        return ApiResponse::success(new HrRequestResource($hrRequest->load(self::RELATIONS)), 'Keputusan persetujuan disimpan.');
    }

    public function reject(Request $request, HrRequest $hrRequest): JsonResponse
    {
        $this->authorize('reject', $hrRequest);
        $data = $request->validate(['note' => ['required', 'string', 'max:1000']], ['note.required' => 'Alasan penolakan wajib diisi.']);

        $hrRequest = $this->requests->reject($hrRequest, $this->actor($request), $data['note']);

        return ApiResponse::success(new HrRequestResource($hrRequest->load(self::RELATIONS)), 'Pengajuan ditolak.');
    }

    public function process(Request $request, HrRequest $hrRequest): JsonResponse
    {
        $this->authorize('process', $hrRequest);

        $hrRequest = $this->requests->markProcessed($hrRequest, $this->actor($request));

        return ApiResponse::success(new HrRequestResource($hrRequest->load(self::RELATIONS)), 'Pengajuan ditandai selesai diproses.');
    }

    public function cancel(HrRequest $hrRequest): JsonResponse
    {
        $this->authorize('cancel', $hrRequest);

        $hrRequest = $this->requests->cancel($hrRequest);

        return ApiResponse::success(new HrRequestResource($hrRequest->load(self::RELATIONS)), 'Pengajuan dibatalkan.');
    }

    private function actor(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}
