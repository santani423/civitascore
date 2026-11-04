<?php

namespace Modules\Academic\Requests\Concerns;

use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;
use Modules\Academic\Models\ClassSection;
use Modules\Academic\Support\AcademicClock;

/**
 * Aturan tanggal pertemuan yang sama untuk rekam batch dan sesi presensi
 * mandiri: tidak boleh di masa depan menurut zona waktu universitas, dan
 * harus jatuh di dalam periode semester kelas itu.
 */
trait ValidatesAttendanceMeeting
{
    /**
     * @return array<int, string>
     */
    protected function meetingDateRules(AcademicClock $clock): array
    {
        return ['required', 'date', 'before_or_equal:'.$clock->now()->toDateString()];
    }

    protected function validateMeetingWithinTerm(Validator $validator): void
    {
        $classSection = $this->route('classSection');

        if (! $classSection instanceof ClassSection || $validator->errors()->has('meeting_date')) {
            return;
        }

        $term = $classSection->loadMissing('academicTerm')->academicTerm;
        $date = Carbon::parse((string) $this->input('meeting_date'))->toDateString();

        if ($date < $term->start_date->toDateString() || $date > $term->end_date->toDateString()) {
            $validator->errors()->add(
                'meeting_date',
                sprintf(
                    'Tanggal pertemuan harus berada dalam periode semester kelas ini (%s s.d. %s).',
                    $term->start_date->toDateString(),
                    $term->end_date->toDateString(),
                ),
            );
        }
    }
}
