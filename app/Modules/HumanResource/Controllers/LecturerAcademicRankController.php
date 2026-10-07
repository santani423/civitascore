<?php

namespace Modules\HumanResource\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Http\ApiResponse;
use App\Support\Http\ListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Academic\Models\Employee;
use Modules\HumanResource\Models\LecturerAcademicRank;
use Modules\HumanResource\Requests\UpsertAcademicRankRequest;
use Modules\HumanResource\Resources\LecturerAcademicRankResource;
use Modules\HumanResource\Services\EmployeeHistoryService;

/**
 * Riwayat jabatan akademik dosen.
 */
class LecturerAcademicRankController extends Controller
{
    public function __construct(private readonly EmployeeHistoryService $history) {}

    public function all(Request $request): JsonResponse
    {
        $this->authorize('viewAny', LecturerAcademicRank::class);

        $paginator = ListQuery::paginate(
            query: LecturerAcademicRank::query()->with(['employee', 'decreeFile'])->latest('start_date'),
            request: $request,
            searchable: ['decree_number'],
            filterable: ['academic_rank', 'is_current', 'employee_id'],
            sortable: ['start_date'],
        );

        return ApiResponse::paginated(LecturerAcademicRankResource::collection($paginator));
    }

    public function index(Employee $employee): JsonResponse
    {
        $this->authorize('viewAny', LecturerAcademicRank::class);
        $this->authorize('hr.employees.view', $employee);

        $records = $employee->academicRankHistories()->with('decreeFile')->orderByDesc('start_date')->get();

        return ApiResponse::success(LecturerAcademicRankResource::collection($records));
    }

    public function store(UpsertAcademicRankRequest $request, Employee $employee): JsonResponse
    {
        $this->authorize('create', LecturerAcademicRank::class);

        $record = $this->history->saveAcademicRank($employee, $request->validated(), $this->actor($request));

        return ApiResponse::success(new LecturerAcademicRankResource($record->load('decreeFile')), 'Riwayat jabatan akademik ditambahkan.', status: 201);
    }

    public function update(UpsertAcademicRankRequest $request, LecturerAcademicRank $lecturerAcademicRank): JsonResponse
    {
        $this->authorize('update', $lecturerAcademicRank);
        $employee = $lecturerAcademicRank->employee;
        abort_if($employee === null, 404);

        $record = $this->history->saveAcademicRank($employee, $request->validated(), $this->actor($request), $lecturerAcademicRank);

        return ApiResponse::success(new LecturerAcademicRankResource($record->load('decreeFile')), 'Riwayat jabatan akademik diperbarui.');
    }

    public function destroy(LecturerAcademicRank $lecturerAcademicRank): JsonResponse
    {
        $this->authorize('delete', $lecturerAcademicRank);

        $this->history->deleteAcademicRank($lecturerAcademicRank);

        return ApiResponse::success(null, 'Riwayat jabatan akademik dihapus.');
    }

    private function actor(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}
