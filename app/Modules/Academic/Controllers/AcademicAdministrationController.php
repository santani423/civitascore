<?php

namespace Modules\Academic\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Academic\Enums\LetterGrade;
use Modules\Academic\Models\AcademicTerm;
use Modules\Academic\Models\ClassSection;
use Modules\Academic\Models\Course;
use Modules\Academic\Models\CoursePrerequisite;
use Modules\Academic\Models\Lecturer;
use Modules\Academic\Models\Student;
use Modules\Academic\Services\ClassTeachingService;
use Modules\Academic\Support\PortalFormatter;

/**
 * Pengaturan data akademik yang dikonsumsi Portal Mahasiswa, oleh Bagian
 * Akademik: periode KRS, jadwal & dosen pengampu kelas (dengan deteksi
 * bentrok dosen/ruangan, RANCANGAN-APLIKASI.md §4.9), prasyarat mata
 * kuliah, dan dosen wali mahasiswa. Semua referensi (dosen, mata kuliah)
 * dicari lewat query ter-scope tenant — id milik universitas lain = 404/422.
 */
class AcademicAdministrationController extends Controller
{
    public function __construct(private readonly ClassTeachingService $teaching) {}

    public function terms(): JsonResponse
    {
        return ApiResponse::success(AcademicTerm::query()
            ->orderByDesc('start_date')
            ->get()
            ->map(fn (AcademicTerm $term) => PortalFormatter::term($term))
            ->values());
    }

    public function updateKrsPeriod(Request $request, AcademicTerm $academicTerm): JsonResponse
    {
        $data = $request->validate([
            'krs_start_date' => ['nullable', 'date', 'required_with:krs_end_date'],
            'krs_end_date' => ['nullable', 'date', 'after_or_equal:krs_start_date', 'required_with:krs_start_date'],
        ], [
            'krs_end_date.after_or_equal' => 'Tanggal akhir KRS harus sama dengan atau setelah tanggal mulai.',
        ]);

        $academicTerm->update($data);

        return ApiResponse::success(PortalFormatter::term($academicTerm->refresh()), 'Periode KRS disimpan.');
    }

    /**
     * Bentrok dosen bisa dipaksa dengan `force` + `reason`; bentrok ruangan
     * tidak — lihat ClassTeachingService.
     */
    public function updateTeaching(Request $request, ClassSection $classSection): JsonResponse
    {
        $this->authorize('update', $classSection);

        $data = $request->validate([
            'lecturer_id' => ['nullable', 'string', 'max:26'],
            'schedules' => ['present', 'array', 'max:7'],
            'schedules.*.day_of_week' => ['required', 'integer', 'between:1,7'],
            'schedules.*.start_time' => ['required', 'date_format:H:i'],
            'schedules.*.end_time' => ['required', 'date_format:H:i', 'after:schedules.*.start_time'],
            'schedules.*.room' => ['nullable', 'string', 'max:100'],
            'force' => ['sometimes', 'boolean'],
            'reason' => ['required_if_accepted:force', 'nullable', 'string', 'min:10', 'max:500'],
        ], [
            'schedules.*.end_time.after' => 'Jam selesai harus setelah jam mulai.',
            'reason.required_if_accepted' => 'Alasan wajib diisi untuk memaksa jadwal yang bentrok.',
            'reason.min' => 'Alasan minimal 10 karakter.',
        ]);

        $lecturer = null;

        if (! empty($data['lecturer_id'])) {
            $lecturer = Lecturer::query()->where('is_active', true)->whereKey($data['lecturer_id'])->first()
                ?? throw ValidationException::withMessages(['lecturer_id' => 'Dosen tidak ditemukan atau tidak aktif.']);
        }

        $result = $this->teaching->update(
            $classSection,
            $lecturer,
            $data['schedules'],
            force: (bool) ($data['force'] ?? false),
            reason: $data['reason'] ?? null,
        );

        return ApiResponse::success(
            PortalFormatter::classSection($classSection),
            $result['overridden_conflicts'] === []
                ? 'Jadwal & dosen pengampu kelas disimpan.'
                : 'Jadwal & dosen pengampu kelas disimpan. Bentrok jadwal dosen dipaksa dan dicatat di audit log.',
            meta: [
                'overridden_conflicts' => $result['overridden_conflicts'],
                'warnings' => ['student_conflicts' => $result['student_conflicts']],
            ],
        );
    }

    public function updatePrerequisites(Request $request, Course $course): JsonResponse
    {
        $this->authorize('update', $course);

        $data = $request->validate([
            'prerequisites' => ['present', 'array', 'max:10'],
            'prerequisites.*.course_id' => ['required', 'string', 'distinct', 'max:26'],
            'prerequisites.*.min_letter_grade' => ['nullable', Rule::enum(LetterGrade::class)],
        ]);

        $rows = collect($data['prerequisites']);
        $found = Course::query()->whereIn('id', $rows->pluck('course_id'))->pluck('id');

        if ($found->count() !== $rows->count()) {
            throw ValidationException::withMessages(['prerequisites' => 'Salah satu mata kuliah prasyarat tidak ditemukan.']);
        }

        if ($rows->contains('course_id', $course->id)) {
            throw ValidationException::withMessages(['prerequisites' => 'Mata kuliah tidak dapat menjadi prasyarat bagi dirinya sendiri.']);
        }

        $cyclic = CoursePrerequisite::query()
            ->whereIn('course_id', $rows->pluck('course_id'))
            ->where('prerequisite_course_id', $course->id)
            ->with('course')
            ->first();

        if ($cyclic !== null) {
            throw ValidationException::withMessages(['prerequisites' => "{$cyclic->course->name} sudah mensyaratkan {$course->name} — prasyarat tidak boleh saling melingkar."]);
        }

        DB::transaction(function () use ($course, $rows): void {
            $course->prerequisites()->delete();

            foreach ($rows as $row) {
                $course->prerequisites()->create([
                    'university_id' => $course->university_id,
                    'prerequisite_course_id' => $row['course_id'],
                    'min_letter_grade' => $row['min_letter_grade'] ?? null,
                ]);
            }
        });

        return ApiResponse::success(
            $course->prerequisites()->with('prerequisite')->get()->map(fn (CoursePrerequisite $prerequisite): array => [
                'course_id' => $prerequisite->prerequisite_course_id,
                'code' => $prerequisite->prerequisite->code,
                'name' => $prerequisite->prerequisite->name,
                'min_letter_grade' => ($prerequisite->min_letter_grade ?? LetterGrade::PASSING)->value,
            ])->values(),
            'Prasyarat mata kuliah disimpan.',
        );
    }

    public function prerequisites(Course $course): JsonResponse
    {
        $this->authorize('view', $course);

        return ApiResponse::success($course->prerequisites()->with('prerequisite')->get()->map(fn (CoursePrerequisite $prerequisite): array => [
            'course_id' => $prerequisite->prerequisite_course_id,
            'code' => $prerequisite->prerequisite->code,
            'name' => $prerequisite->prerequisite->name,
            'min_letter_grade' => ($prerequisite->min_letter_grade ?? LetterGrade::PASSING)->value,
        ])->values());
    }

    public function assignAdvisor(Request $request, Student $student): JsonResponse
    {
        $this->authorize('update', $student);

        $data = $request->validate(['lecturer_id' => ['nullable', 'string', 'max:26']]);

        $lecturer = null;

        if (! empty($data['lecturer_id'])) {
            $lecturer = Lecturer::query()->where('is_active', true)->find($data['lecturer_id'])
                ?? throw ValidationException::withMessages(['lecturer_id' => 'Dosen tidak ditemukan atau tidak aktif.']);
        }

        $student->update(['academic_advisor_id' => $lecturer?->id]);

        return ApiResponse::success([
            'student_id' => $student->id,
            'academic_advisor' => PortalFormatter::lecturer($lecturer),
        ], $lecturer ? 'Dosen wali ditetapkan.' : 'Dosen wali dilepas.');
    }
}
