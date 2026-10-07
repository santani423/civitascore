<?php

namespace Modules\Academic\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Modules\Academic\Controllers\Concerns\ResolvesCurrentStudent;
use Modules\Academic\Models\KrsItem;
use Modules\Academic\Services\StudentAttendanceService;
use Modules\Academic\Services\StudentCalendarService;
use Modules\Academic\Services\StudentGradeService;
use Modules\Academic\Services\StudentScheduleService;

/**
 * Portal Mahasiswa — data akademik read-only: jadwal, presensi, nilai, KHS,
 * transkrip, dan kalender. Seluruhnya dari data milik mahasiswa yang login.
 */
class StudentAcademicRecordController extends Controller
{
    use ResolvesCurrentStudent;

    public function __construct(
        private readonly StudentScheduleService $schedules,
        private readonly StudentAttendanceService $attendance,
        private readonly StudentGradeService $grades,
        private readonly StudentCalendarService $calendar,
    ) {}

    public function schedule(Request $request): JsonResponse
    {
        return ApiResponse::success($this->schedules->overview($this->currentStudent($request)));
    }

    public function attendance(Request $request): JsonResponse
    {
        $request->validate(['academic_term_id' => ['nullable', 'string', 'max:26']]);

        return ApiResponse::success($this->attendance->summary(
            $this->currentStudent($request),
            $request->query('academic_term_id') ?: null,
        ));
    }

    public function attendanceDetail(Request $request, KrsItem $krsItem): JsonResponse
    {
        $this->currentStudent($request);
        $this->authorize('viewOwn', $krsItem);

        return ApiResponse::success($this->attendance->detail($krsItem));
    }

    public function grades(Request $request): JsonResponse
    {
        return ApiResponse::success($this->grades->grades($this->currentStudent($request)));
    }

    public function khs(Request $request): JsonResponse
    {
        $request->validate(['academic_term_id' => ['nullable', 'string', 'max:26']]);

        return ApiResponse::success($this->grades->khs(
            $this->currentStudent($request),
            $request->query('academic_term_id') ?: null,
        ));
    }

    public function transcript(Request $request): JsonResponse
    {
        return ApiResponse::success($this->grades->transcript($this->currentStudent($request)));
    }

    public function calendar(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
        ], [
            'from.required' => 'Tanggal awal wajib diisi.',
            'to.after_or_equal' => 'Tanggal akhir harus sama dengan atau setelah tanggal awal.',
        ]);

        $from = CarbonImmutable::parse($validated['from']);
        $to = CarbonImmutable::parse($validated['to']);

        if ($from->diffInDays($to) > StudentCalendarService::MAX_RANGE_DAYS) {
            throw ValidationException::withMessages(['to' => 'Rentang kalender maksimal '.StudentCalendarService::MAX_RANGE_DAYS.' hari.']);
        }

        return ApiResponse::success($this->calendar->events($this->currentStudent($request), $from, $to));
    }
}
