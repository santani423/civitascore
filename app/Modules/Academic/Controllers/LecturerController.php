<?php

namespace Modules\Academic\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use App\Support\Http\ListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Academic\Models\Faculty;
use Modules\Academic\Models\Lecturer;
use Modules\Academic\Requests\StoreLecturerRequest;
use Modules\Academic\Requests\UpdateLecturerRequest;
use Modules\Academic\Resources\LecturerResource;

class LecturerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Lecturer::class);

        $paginator = ListQuery::paginate(
            query: Lecturer::query()->with('faculty')->orderBy('name'),
            request: $request,
            searchable: ['name', 'nidn'],
            filterable: ['faculty_id', 'is_active'],
            sortable: ['name'],
        );

        return ApiResponse::paginated(LecturerResource::collection($paginator));
    }

    public function show(Lecturer $lecturer): JsonResponse
    {
        $this->authorize('view', $lecturer);

        return ApiResponse::success(new LecturerResource($lecturer->load('faculty')));
    }

    public function store(StoreLecturerRequest $request): JsonResponse
    {
        $this->authorize('create', Lecturer::class);

        $data = $request->validated();

        // Scoped findOrFail (not Rule::exists in the Request) so a
        // faculty_id belonging to another tenant reads as "not found"
        // rather than leaking cross-tenant existence — same reasoning as
        // StoreStudentRequest/ExamService::createExam().
        if (! empty($data['faculty_id'])) {
            Faculty::query()->findOrFail($data['faculty_id']);
        }

        $lecturer = Lecturer::query()->create($data);

        return ApiResponse::success(
            new LecturerResource($lecturer->load('faculty')),
            'Dosen berhasil ditambahkan.',
            status: 201,
        );
    }

    public function update(UpdateLecturerRequest $request, Lecturer $lecturer): JsonResponse
    {
        $this->authorize('update', $lecturer);

        $data = $request->validated();

        if (! empty($data['faculty_id'])) {
            Faculty::query()->findOrFail($data['faculty_id']);
        }

        $lecturer->update($data);

        return ApiResponse::success(new LecturerResource($lecturer->load('faculty')), 'Dosen berhasil diperbarui.');
    }

    public function destroy(Lecturer $lecturer): JsonResponse
    {
        $this->authorize('delete', $lecturer);

        $lecturer->delete();

        return ApiResponse::success(null, 'Dosen berhasil dihapus.');
    }
}
