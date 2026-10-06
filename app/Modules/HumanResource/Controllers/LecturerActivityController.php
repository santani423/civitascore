<?php

namespace Modules\HumanResource\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Modules\Academic\Models\Employee;
use Modules\HumanResource\Models\LecturerActivity;
use Modules\HumanResource\Requests\UpsertLecturerActivityRequest;
use Modules\HumanResource\Resources\LecturerActivityResource;
use Modules\HumanResource\Services\Concerns\SavesRecordWithFiles;

/**
 * Penelitian & pengabdian dosen.
 */
class LecturerActivityController extends Controller
{
    use SavesRecordWithFiles;

    public function index(Request $request, Employee $employee): JsonResponse
    {
        $this->authorize('viewAny', LecturerActivity::class);
        $this->authorize('hr.employees.view', $employee);

        $records = $employee->lecturerActivities()
            ->with('documentFile')
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->query('type')))
            ->orderByDesc('year')
            ->get();

        return ApiResponse::success(LecturerActivityResource::collection($records));
    }

    public function store(UpsertLecturerActivityRequest $request, Employee $employee): JsonResponse
    {
        $this->authorize('create', LecturerActivity::class);

        if (! $employee->isLecturer()) {
            throw ValidationException::withMessages(['employee_id' => 'Penelitian & pengabdian hanya untuk pegawai berjenis dosen.']);
        }

        $record = $this->saveWithFiles(
            new LecturerActivity(['employee_id' => $employee->id, 'university_id' => $employee->university_id]),
            $request->validated(),
            ['document_file_id'],
            $this->actor($request),
        );

        return ApiResponse::success(new LecturerActivityResource($record->load('documentFile')), 'Data berhasil ditambahkan.', status: 201);
    }

    public function update(UpsertLecturerActivityRequest $request, LecturerActivity $lecturerActivity): JsonResponse
    {
        $this->authorize('update', $lecturerActivity);

        $record = $this->saveWithFiles($lecturerActivity, $request->validated(), ['document_file_id'], $this->actor($request));

        return ApiResponse::success(new LecturerActivityResource($record->load('documentFile')), 'Data berhasil diperbarui.');
    }

    public function destroy(LecturerActivity $lecturerActivity): JsonResponse
    {
        $this->authorize('delete', $lecturerActivity);

        $lecturerActivity->delete();

        return ApiResponse::success(null, 'Data berhasil dihapus.');
    }

    private function actor(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}
