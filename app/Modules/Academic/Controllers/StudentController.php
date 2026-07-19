<?php

namespace Modules\Academic\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use App\Support\Http\ListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Academic\Models\Student;
use Modules\Academic\Resources\StudentResource;

class StudentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Student::class);

        $paginator = ListQuery::paginate(
            query: Student::query()->with('studyProgram')->latest('enrolled_at'),
            request: $request,
            searchable: ['name', 'nim'],
            filterable: ['study_program_id', 'status'],
            sortable: ['name', 'admission_year', 'enrolled_at'],
        );

        return ApiResponse::paginated(StudentResource::collection($paginator));
    }

    public function show(Student $student): JsonResponse
    {
        $this->authorize('view', $student);

        return ApiResponse::success(new StudentResource($student->load('studyProgram')));
    }
}
