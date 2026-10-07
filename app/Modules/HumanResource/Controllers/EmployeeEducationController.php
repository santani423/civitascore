<?php

namespace Modules\HumanResource\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Http\ApiResponse;
use App\Support\Http\ListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Academic\Models\Employee;
use Modules\HumanResource\Models\EmployeeEducation;
use Modules\HumanResource\Requests\UpsertEducationRequest;
use Modules\HumanResource\Resources\EmployeeEducationResource;
use Modules\HumanResource\Services\EmployeeHistoryService;

class EmployeeEducationController extends Controller
{
    public function __construct(private readonly EmployeeHistoryService $history) {}

    /**
     * Seluruh riwayat pendidikan lintas pegawai (menu Pengembangan SDM →
     * Pendidikan).
     */
    public function all(Request $request): JsonResponse
    {
        $this->authorize('viewAny', EmployeeEducation::class);

        $paginator = ListQuery::paginate(
            query: EmployeeEducation::query()->with(['employee', 'documentFile'])->latest(),
            request: $request,
            searchable: ['institution', 'major'],
            filterable: ['level', 'employee_id'],
            sortable: ['graduation_year', 'created_at'],
        );

        return ApiResponse::paginated(EmployeeEducationResource::collection($paginator));
    }

    public function index(Employee $employee): JsonResponse
    {
        $this->authorize('viewAny', EmployeeEducation::class);
        $this->authorize('hr.employees.view', $employee);

        $records = $employee->educations()->with('documentFile')->orderByDesc('graduation_year')->get();

        return ApiResponse::success(EmployeeEducationResource::collection($records));
    }

    public function store(UpsertEducationRequest $request, Employee $employee): JsonResponse
    {
        $this->authorize('create', EmployeeEducation::class);

        $record = $this->history->saveEducation($employee, $request->validated(), $this->actor($request));

        return ApiResponse::success(new EmployeeEducationResource($record->load('documentFile')), 'Riwayat pendidikan ditambahkan.', status: 201);
    }

    public function update(UpsertEducationRequest $request, EmployeeEducation $education): JsonResponse
    {
        $this->authorize('update', $education);
        $employee = $education->employee;
        abort_if($employee === null, 404);

        $record = $this->history->saveEducation($employee, $request->validated(), $this->actor($request), $education);

        return ApiResponse::success(new EmployeeEducationResource($record->load('documentFile')), 'Riwayat pendidikan diperbarui.');
    }

    public function destroy(EmployeeEducation $education): JsonResponse
    {
        $this->authorize('delete', $education);

        $this->history->deleteEducation($education);

        return ApiResponse::success(null, 'Riwayat pendidikan dihapus.');
    }

    private function actor(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}
