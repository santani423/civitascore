<?php

namespace Modules\HumanResource\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Http\ApiResponse;
use App\Support\Http\ListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Academic\Models\Employee;
use Modules\HumanResource\Models\EmployeeTransfer;
use Modules\HumanResource\Requests\StoreTransferRequest;
use Modules\HumanResource\Resources\EmployeeTransferResource;
use Modules\HumanResource\Services\TransferService;

/**
 * Penempatan & mutasi. Mutasi tidak bisa diubah/dihapus setelah berlaku —
 * koreksi dilakukan dengan mencatat mutasi baru, supaya riwayat
 * penempatan selalu utuh.
 */
class EmployeeTransferController extends Controller
{
    private const RELATIONS = ['employee', 'documentFile', 'creator'];

    public function __construct(private readonly TransferService $transfers) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', EmployeeTransfer::class);

        $paginator = ListQuery::paginate(
            query: EmployeeTransfer::query()->with(self::RELATIONS)
                ->when(! $request->filled('sort'), fn ($query) => $query->latest('effective_date')),
            request: $request,
            searchable: ['decree_number', 'to_work_unit_name', 'from_work_unit_name', 'to_position_name'],
            filterable: ['employee_id', 'status', 'to_work_unit_id', 'from_work_unit_id'],
            sortable: ['effective_date', 'created_at'],
        );

        return ApiResponse::paginated(EmployeeTransferResource::collection($paginator));
    }

    public function store(StoreTransferRequest $request): JsonResponse
    {
        $this->authorize('create', EmployeeTransfer::class);
        $data = $request->validated();
        $employee = Employee::query()->whereKey($data['employee_id'])->firstOrFail();
        unset($data['employee_id']);

        /** @var User $actor */
        $actor = $request->user();
        $transfer = $this->transfers->create($employee, $data, $actor);

        return ApiResponse::success(
            new EmployeeTransferResource($transfer->load(self::RELATIONS)),
            $transfer->applied_at !== null ? 'Mutasi dicatat dan langsung diterapkan.' : 'Mutasi dijadwalkan sesuai tanggal efektif.',
            status: 201,
        );
    }

    public function cancel(EmployeeTransfer $transfer): JsonResponse
    {
        $this->authorize('update', $transfer);

        $transfer = $this->transfers->cancel($transfer);

        return ApiResponse::success(new EmployeeTransferResource($transfer->load(self::RELATIONS)), 'Mutasi dibatalkan.');
    }
}
