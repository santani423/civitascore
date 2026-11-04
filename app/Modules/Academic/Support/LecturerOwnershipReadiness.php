<?php

namespace Modules\Academic\Support;

use Modules\Academic\Models\ClassSection;

/**
 * Kesiapan sebelum flag `academic.lecturer_ownership` dinyalakan: setiap
 * kelas aktif di semester yang belum berakhir harus sudah punya dosen
 * pengampu. Kelas tanpa pengampu tidak bisa diakses dosen mana pun begitu
 * kepemilikan ditegakkan, jadi flag tidak boleh menyala sebelum data lengkap.
 */
class LecturerOwnershipReadiness
{
    public function __construct(private readonly AcademicClock $clock) {}

    public function unassignedClassCount(): int
    {
        return ClassSection::query()
            ->where('is_active', true)
            ->whereNull('lecturer_id')
            ->whereHas('academicTerm', fn ($term) => $term->whereDate('end_date', '>=', $this->clock->now()->toDateString()))
            ->count();
    }
}
