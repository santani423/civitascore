<?php

namespace Modules\Academic\Observers;

use Illuminate\Support\Facades\Auth;
use Modules\Academic\Models\AcademicTerm;
use Modules\Academic\Models\Student;
use Modules\Academic\Models\StudentStatusHistory;

/**
 * Mencatat setiap perubahan students.status ke student_status_histories —
 * dari jalur mana pun (pengajuan cuti/aktif kembali yang disetujui,
 * Bagian Akademik, dsb.), supaya timeline Riwayat Akademik mahasiswa tidak
 * bergantung pada setiap pemanggil untuk ingat menulis riwayatnya sendiri.
 */
class StudentObserver
{
    public function updated(Student $student): void
    {
        if (! $student->wasChanged('status')) {
            return;
        }

        StudentStatusHistory::query()->create([
            'university_id' => $student->university_id,
            'student_id' => $student->id,
            'from_status' => $student->getOriginal('status'),
            'to_status' => $student->status,
            'effective_date' => now()->toDateString(),
            'academic_term_id' => AcademicTerm::query()->where('is_current', true)->value('id'),
            'reason' => $student->statusChangeReason,
            'changed_by' => Auth::id(),
        ]);

        $student->statusChangeReason = null;
    }
}
