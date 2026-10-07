<?php

namespace Modules\HumanResource\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Http\ApiResponse;
use App\Support\Http\ListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Academic\Models\Employee;
use Modules\HumanResource\Models\EmployeePosition;
use Modules\HumanResource\Requests\UpsertEmployeePositionRequest;
use Modules\HumanResource\Resources\EmployeePositionResource;
use Modules\HumanResource\Services\EmployeeHistoryService;

/**
 * Riwayat jabatan (struktural & fungsional) pegawai.
 */
class EmployeePositionController extends Controller
{
    public function __construct(private readonly EmployeeHistoryService $history) {}

    public function all(Request $request): JsonResponse
    {
        $this->authorize('viewAny', EmployeePosition::class);

        $paginator = ListQuery::paginate(
            query: EmployeePosition::query()->with(['employee', 'decreeFile'])->latest('start_date'),
            request: $request,
            searchable: ['position_name', 'work_unit_name', 'decree_number'],
            filterable: ['position_id', 'position_type', 'work_unit_id', 'is_current', 'employee_id'],
            sortable: ['start_date', 'end_date'],
        );

        return ApiResponse::paginated(EmployeePositionResource::collection($paginator));
    }

    public function index(Employee $employee): JsonResponse
    {
        $this->authorize('viewAny', EmployeePosition::class);
        $this->authorize('hr.employees.view', $employee);

        $records = $employee->positionHistories()->with('decreeFile')->orderByDesc('start_date')->get();

        return ApiResponse::success(EmployeePositionResource::collection($records));
    }

    public function store(UpsertEmployeePositionRequest $request, Employee $employee): JsonResponse
    {
        $this->authorize('create', EmployeePosition::class);

        $record = $this->history->savePosition($employee, $request->validated(), $this->actor($request));

        return ApiResponse::success(new EmployeePositionResource($record->load('decreeFile')), 'Riwayat jabatan ditambahkan.', status: 201);
    }

    public function update(UpsertEmployeePositionRequest $request, EmployeePosition $employeePosition): JsonResponse
    {
        $this->authorize('update', $employeePosition);
        $employee = $employeePosition->employee;
        abort_if($employee === null, 404);

        $record = $this->history->savePosition($employee, $request->validated(), $this->actor($request), $employeePosition);

        return ApiResponse::success(new EmployeePositionResource($record->load('decreeFile')), 'Riwayat jabatan diperbarui.');
    }

    public function destroy(EmployeePosition $employeePosition): JsonResponse
    {
        $this->authorize('delete', $employeePosition);

        $this->history->deletePosition($employeePosition);

        return ApiResponse::success(null, 'Riwayat jabatan dihapus.');
    }

    private function actor(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}
