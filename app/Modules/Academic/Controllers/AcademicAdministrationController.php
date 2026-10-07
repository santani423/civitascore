<?php

namespace Modules\Academic\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use App\Support\Http\Exceptions\ConflictException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Academic\Enums\LetterGrade;
use Modules\Academic\Models\AcademicTerm;
use Modules\Academic\Models\ClassSchedule;
use Modules\Academic\Models\ClassSection;
use Modules\Academic\Models\Course;
use Modules\Academic\Models\CoursePrerequisite;
use Modules\Academic\Models\Lecturer;
use Modules\Academic\Models\Student;
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
        ], [
            'schedules.*.end_time.after' => 'Jam selesai harus setelah jam mulai.',
        ]);

        $lecturer = null;

        if (! empty($data['lecturer_id'])) {
            $lecturer = Lecturer::query()->where('is_active', true)->find($data['lecturer_id'])
                ?? throw ValidationException::withMessages(['lecturer_id' => 'Dosen tidak ditemukan atau tidak aktif.']);
        }

        $schedules = collect($data['schedules'])->map(fn (array $row) => new ClassSchedule($row));
        $this->assertNoClash($classSection, $lecturer, $schedules->all());

        DB::transaction(function () use ($classSection, $lecturer, $data): void {
            $classSection->update(['lecturer_id' => $lecturer?->id]);
            $classSection->schedules()->delete();

            foreach ($data['schedules'] as $row) {
                $classSection->schedules()->create([
                    'university_id' => $classSection->university_id,
                    'day_of_week' => $row['day_of_week'],
                    'start_time' => $row['start_time'],
                    'end_time' => $row['end_time'],
                    'room' => $row['room'] ?? null,
                ]);
            }
        });

        return ApiResponse::success(
            PortalFormatter::classSection($classSection->refresh()->load(['course', 'lecturer', 'schedules'])),
            'Jadwal & dosen pengampu kelas disimpan.',
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

    /**
     * Bentrok dosen (dosen sama, jam beririsan) atau ruangan (ruangan sama,
     * jam beririsan) dengan kelas lain di semester yang sama.
     *
     * @param  array<int, ClassSchedule>  $schedules
     */
    private function assertNoClash(ClassSection $classSection, ?Lecturer $lecturer, array $schedules): void
    {
        foreach ($schedules as $index => $schedule) {
            foreach (array_slice($schedules, $index + 1) as $other) {
                if ($schedule->overlaps($other)) {
                    throw new ConflictException('Dua jadwal pada kelas ini saling bentrok.');
                }
            }
        }

        $others = ClassSchedule::query()
            ->whereHas('classSection', fn ($query) => $query
                ->where('academic_term_id', $classSection->academic_term_id)
                ->whereKeyNot($classSection->id))
            ->with('classSection.course', 'classSection.lecturer')
            ->get();

        foreach ($schedules as $schedule) {
            foreach ($others as $other) {
                if (! $schedule->overlaps($other)) {
                    continue;
                }

                if ($lecturer !== null && $other->classSection->lecturer_id === $lecturer->id) {
                    throw new ConflictException("Jadwal bentrok dengan kelas {$other->classSection->course->name} {$other->classSection->class_code} yang juga diampu {$lecturer->name} (".PortalFormatter::scheduleText($other).').');
                }

                if ($schedule->room !== null && $other->room !== null && strcasecmp($schedule->room, $other->room) === 0) {
                    throw new ConflictException("Ruangan {$other->room} sudah dipakai kelas {$other->classSection->course->name} {$other->classSection->class_code} (".PortalFormatter::scheduleText($other).').');
                }
            }
        }
    }
}
