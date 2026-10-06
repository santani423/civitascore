<?php

namespace Modules\Academic\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use App\Support\Http\ListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Academic\Enums\KrsItemStatus;
use Modules\Academic\Models\Student;
use Modules\Academic\Models\StudyProgram;
use Modules\Academic\Requests\StoreStudentRequest;
use Modules\Academic\Resources\StudentResource;
use Modules\Academic\Services\AcademicRecordService;

class StudentController extends Controller
{
    public function __construct(private readonly AcademicRecordService $records) {}

    public function store(StoreStudentRequest $request): JsonResponse
    {
        $this->authorize('create', Student::class);

        // Scoped findOrFail (not Rule::exists in the Request) so a
        // study_program_id belonging to another tenant reads as "not
        // found" rather than leaking cross-tenant existence — same
        // reasoning as StoreKrsItemRequest/ExamService::createExam().
        StudyProgram::query()->findOrFail((string) $request->validated('study_program_id'));

        $student = Student::query()->create($request->validated());

        return ApiResponse::success(
            new StudentResource($student->load('studyProgram')),
            'Mahasiswa berhasil ditambahkan.',
            status: 201,
        );
    }

    public function transcript(Student $student): JsonResponse
    {
        $this->authorize('viewTranscript', $student);

        return ApiResponse::success($this->records->transcript($student));
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Student::class);

        $query = Student::query()->with('studyProgram')->latest('enrolled_at');

        // `class_section_id` isn't a column on `students` (kelas berupa
        // pendaftaran KRS per mata kuliah, bukan kolom langsung), jadi tidak
        // bisa lewat `filterable` milik ListQuery — diterapkan manual di sini.
        if ($classSectionId = $request->input('filter.class_section_id')) {
            $query->whereHas('krsItems', function ($krsQuery) use ($classSectionId): void {
                $krsQuery->where('class_section_id', $classSectionId)->where('status', KrsItemStatus::Enrolled);
            });
        }

        $paginator = ListQuery::paginate(
            query: $query,
            request: $request,
            searchable: ['name', 'nim'],
            filterable: ['study_program_id', 'status', 'admission_year'],
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
