<?php

namespace Modules\Academic\Support;

use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Modules\Academic\Models\Lecturer;

/**
 * Satu-satunya jalur "user login ↔ dosen" (Tahap 0.4). Dua tautan bisa
 * menunjuk orang yang sama:
 *
 * 1. lecturers.user_id — tautan resmi, ditulis LecturerAccountService;
 * 2. employees.user_id → lecturers.employee_id — tautan Modul SDM, satu-
 *    satunya tautan bagi dosen yang akunnya dibuat dari data pegawai
 *    sebelum lecturers.user_id ada.
 *
 * Tautan (1) selalu menang. Tautan (2) hanya dipakai untuk dosen yang
 * belum punya lecturers.user_id — dosen yang sudah tertaut ke akun lain
 * tidak pernah dikenali lewat akun pegawainya. Pencarian mengikuti tenant
 * aktif (Lecturer & Employee TenantScoped).
 *
 * Didaftarkan scoped (per request) di AcademicServiceProvider supaya
 * pencarian untuk user yang sama tidak diulang di setiap cek otorisasi.
 */
class LecturerIdentity
{
    /** @var array<string, Lecturer|null> */
    private array $lecturers = [];

    public function __construct(private readonly TenantContext $tenant) {}

    /** Dosen milik user ini di tenant aktif, atau null bila user bukan dosen. */
    public function lecturerFor(User $user): ?Lecturer
    {
        $key = $this->tenant->universityId().'|'.$user->id;

        if (! array_key_exists($key, $this->lecturers)) {
            $this->lecturers[$key] = Lecturer::query()->where('user_id', $user->id)->first()
                ?? Lecturer::query()
                    ->whereNull('user_id')
                    ->whereHas('employee', fn ($query) => $query->where('user_id', $user->id))
                    ->first();
        }

        return $this->lecturers[$key];
    }

    /**
     * Akun login dosen (untuk notifikasi, dsb.), atau null bila dosen belum
     * punya akun — pemanggil wajib menangani null.
     */
    public function accountOf(Lecturer $lecturer): ?User
    {
        return $lecturer->user_id !== null
            ? $lecturer->user
            : $lecturer->employee?->user;
    }
}
