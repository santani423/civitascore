<?php

namespace Modules\HumanResource\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Http\ApiResponse;
use App\Support\Http\ListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Academic\Models\Employee;
use Modules\HumanResource\Models\EmployeeTraining;
use Modules\HumanResource\Requests\UpsertTrainingRequest;
use Modules\HumanResource\Resources\EmployeeTrainingResource;
use Modules\HumanResource\Services\Concerns\SavesRecordWithFiles;

class EmployeeTrainingController extends Controller
{
    use SavesRecordWithFiles;

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', EmployeeTraining::class);

        $paginator = ListQuery::paginate(
            query: EmployeeTraining::query()->with(['employee', 'certificateFile'])
                ->when(! $request->filled('sort'), fn ($query) => $query->latest('start_date')),
            request: $request,
            searchable: ['name', 'organizer', 'location'],
            filterable: ['employee_id', 'status'],
            sortable: ['start_date', 'name'],
        );

        return ApiResponse::paginated(EmployeeTrainingResource::collection($paginator));
    }

    public function store(UpsertTrainingRequest $request): JsonResponse
    {
        $this->authorize('create', EmployeeTraining::class);
        $data = $request->validated();
        $employee = Employee::query()->whereKey($data['employee_id'])->firstOrFail();

        $training = $this->saveWithFiles(
            new EmployeeTraining(['employee_id' => $employee->id, 'university_id' => $employee->university_id]),
            $data,
            ['certificate_file_id'],
            $this->actor($request),
        );

        return ApiResponse::success(new EmployeeTrainingResource($training->load(['employee', 'certificateFile'])), 'Pelatihan berhasil ditambahkan.', status: 201);
    }

    public function update(UpsertTrainingRequest $request, EmployeeTraining $training): JsonResponse
    {
        $this->authorize('update', $training);

        $training = $this->saveWithFiles($training, $request->validated(), ['certificate_file_id'], $this->actor($request));

        return ApiResponse::success(new EmployeeTrainingResource($training->load(['employee', 'certificateFile'])), 'Pelatihan berhasil diperbarui.');
    }

    public function destroy(EmployeeTraining $training): JsonResponse
    {
        $this->authorize('delete', $training);

        $training->delete();

        return ApiResponse::success(null, 'Pelatihan dihapus.');
    }

    private function actor(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}
