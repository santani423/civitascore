<?php

namespace Modules\HumanResource\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use App\Support\Http\Exceptions\ConflictException;
use App\Support\Http\ListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Academic\Models\Employee;
use Modules\Academic\Models\Faculty;
use Modules\Academic\Models\StudyProgram;
use Modules\HumanResource\Models\WorkUnit;
use Modules\HumanResource\Requests\UpsertWorkUnitRequest;
use Modules\HumanResource\Resources\WorkUnitResource;
use Modules\HumanResource\Services\EmployeeService;

/**
 * Master unit kerja (menu Kepegawaian → Penempatan).
 */
class WorkUnitController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', WorkUnit::class);

        $paginator = ListQuery::paginate(
            query: WorkUnit::query()->with('parent')->withCount('employees')
                ->when(! $request->filled('sort'), fn ($query) => $query->orderBy('name')),
            request: $request,
            searchable: ['name', 'code'],
            filterable: ['type', 'is_active', 'parent_id'],
            sortable: ['name', 'code'],
        );

        return ApiResponse::paginated(WorkUnitResource::collection($paginator));
    }

    public function store(UpsertWorkUnitRequest $request): JsonResponse
    {
        $this->authorize('create', WorkUnit::class);
        $data = $this->assertReferences($request->validated());

        $unit = WorkUnit::query()->create($data);

        return ApiResponse::success(new WorkUnitResource($unit->load('parent')), 'Unit kerja ditambahkan.', status: 201);
    }

    public function update(UpsertWorkUnitRequest $request, WorkUnit $workUnit, EmployeeService $employees): JsonResponse
    {
        $this->authorize('update', $workUnit);
        $data = $this->assertReferences($request->validated());

        $workUnit->update($data);

        // Nama unit disalin ke kolom lama employees.unit_kerja — ikut
        // diperbarui supaya Dashboard/Laporan tidak menampilkan nama lama.
        if ($workUnit->wasChanged('name')) {
            Employee::query()->where('work_unit_id', $workUnit->id)->each(function (Employee $employee) use ($employees): void {
                $employees->syncLegacyColumns($employee);
                $employee->save();
            });
        }

        return ApiResponse::success(new WorkUnitResource($workUnit->load('parent')), 'Unit kerja diperbarui.');
    }

    public function destroy(WorkUnit $workUnit): JsonResponse
    {
        $this->authorize('delete', $workUnit);

        if ($workUnit->employees()->exists() || $workUnit->children()->exists()) {
            throw new ConflictException('Unit kerja masih memiliki pegawai atau sub-unit. Pindahkan terlebih dahulu, atau nonaktifkan unit ini.');
        }

        $workUnit->delete();

        return ApiResponse::success(null, 'Unit kerja dihapus.');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function assertReferences(array $data): array
    {
        if (! empty($data['parent_id'])) {
            WorkUnit::query()->whereKey($data['parent_id'])->firstOrFail();
        }
        if (! empty($data['faculty_id'])) {
            Faculty::query()->whereKey($data['faculty_id'])->firstOrFail();
        }
        if (! empty($data['study_program_id'])) {
            StudyProgram::query()->whereKey($data['study_program_id'])->firstOrFail();
        }

        return $data;
    }
}
