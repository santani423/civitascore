<?php

namespace Modules\HumanResource\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Http\ApiResponse;
use App\Support\Http\ListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Academic\Models\Employee;
use Modules\HumanResource\Models\EmployeeRank;
use Modules\HumanResource\Requests\UpsertEmployeeRankRequest;
use Modules\HumanResource\Resources\EmployeeRankResource;
use Modules\HumanResource\Services\EmployeeHistoryService;

/**
 * Riwayat kepangkatan (kenaikan pangkat/golongan) pegawai.
 */
class EmployeeRankController extends Controller
{
    public function __construct(private readonly EmployeeHistoryService $history) {}

    public function all(Request $request): JsonResponse
    {
        $this->authorize('viewAny', EmployeeRank::class);

        $paginator = ListQuery::paginate(
            query: EmployeeRank::query()->with(['employee', 'decreeFile'])->latest('start_date'),
            request: $request,
            searchable: ['rank_name', 'grade', 'decree_number'],
            filterable: ['rank_id', 'is_current', 'employee_id'],
            sortable: ['start_date', 'grade'],
        );

        return ApiResponse::paginated(EmployeeRankResource::collection($paginator));
    }

    public function index(Employee $employee): JsonResponse
    {
        $this->authorize('viewAny', EmployeeRank::class);
        $this->authorize('hr.employees.view', $employee);

        $records = $employee->rankHistories()->with('decreeFile')->orderByDesc('start_date')->get();

        return ApiResponse::success(EmployeeRankResource::collection($records));
    }

    public function store(UpsertEmployeeRankRequest $request, Employee $employee): JsonResponse
    {
        $this->authorize('create', EmployeeRank::class);

        $record = $this->history->saveRank($employee, $request->validated(), $this->actor($request));

        return ApiResponse::success(new EmployeeRankResource($record->load('decreeFile')), 'Riwayat kepangkatan ditambahkan.', status: 201);
    }

    public function update(UpsertEmployeeRankRequest $request, EmployeeRank $employeeRank): JsonResponse
    {
        $this->authorize('update', $employeeRank);
        $employee = $employeeRank->employee;
        abort_if($employee === null, 404);

        $record = $this->history->saveRank($employee, $request->validated(), $this->actor($request), $employeeRank);

        return ApiResponse::success(new EmployeeRankResource($record->load('decreeFile')), 'Riwayat kepangkatan diperbarui.');
    }

    public function destroy(EmployeeRank $employeeRank): JsonResponse
    {
        $this->authorize('delete', $employeeRank);

        $this->history->deleteRank($employeeRank);

        return ApiResponse::success(null, 'Riwayat kepangkatan dihapus.');
    }

    private function actor(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}
