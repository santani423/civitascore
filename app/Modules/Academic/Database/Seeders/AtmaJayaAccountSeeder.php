<?php

namespace Modules\Academic\Database\Seeders;

use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Seeder;
use Modules\Academic\Models\Employee;
use Modules\Academic\Models\Lecturer;
use Modules\Academic\Models\Student;
use Modules\HumanResource\Enums\EmployeeType;
use Modules\HumanResource\Enums\EmploymentStatus;
use Modules\HumanResource\Enums\LecturerStatus;
use Modules\HumanResource\Services\EmployeeService;
use Modules\Tenancy\Enums\MembershipStatus;
use Modules\Tenancy\Enums\MembershipType;
use Modules\Tenancy\Models\University;
use Modules\Tenancy\Models\UserUniversity;
use Modules\UserManagement\Models\Role;
use Modules\UserManagement\Models\UserRole;

/**
 * Menautkan seluruh akun ber-email domain atmajaya.com (termasuk subdomain,
 * mis. NIM@student.atmajaya.com) ke Universitas Katolik Indonesia Atma Jaya
 * (kode UAJ):
 *
 * - Akun login mahasiswa sampel yang masih memakai email fallback
 *   StudentUserAccountSeeder (NIM@student.uaj.local) dipindah ke email
 *   kampus NIM@student.atmajaya.com.
 * - Setiap akun @atmajaya.com punya keanggotaan aktif di UAJ dan UAJ menjadi
 *   universitas default-nya.
 * - Setiap akun ber-role Dosen/Dosen PA di UAJ (mis. dosen@atmajaya.com)
 *   punya baris pegawai + dosen, sehingga tampil di menu Dosen dan Modul SDM.
 *
 * Idempotent — aman dijalankan berulang. Jalankan terpisah untuk
 * memperbaiki data yang sudah ada:
 * php artisan db:seed --class="Modules\\Academic\\Database\\Seeders\\AtmaJayaAccountSeeder"
 */
class AtmaJayaAccountSeeder extends Seeder
{
    public const DOMAIN = 'atmajaya.com';

    private const LECTURER_ROLES = ['lecturer', 'academic_advisor'];

    public static function studentEmail(string $nim): string
    {
        return mb_strtolower($nim).'@student.'.self::DOMAIN;
    }

    public function run(): void
    {
        $university = University::query()->where('code', 'UAJ')->first();

        if ($university === null) {
            return;
        }

        $tenant = app(TenantContext::class);
        $tenant->setUniversityId($university->id);

        try {
            $this->moveStudentAccountsToCampusEmail($university);
            $this->linkDomainUsers($university);
            $this->ensureLecturerRecords($university);
        } finally {
            $tenant->setUniversityId(null);
        }
    }

    private function moveStudentAccountsToCampusEmail(University $university): void
    {
        Student::query()
            ->where('university_id', $university->id)
            ->with('user')
            ->chunkById(100, function ($students): void {
                foreach ($students as $student) {
                    $campusEmail = self::studentEmail($student->nim);

                    if ($student->email === null || str_ends_with($student->email, '.local')) {
                        $student->email = $campusEmail;
                        $student->saveQuietly();
                    }

                    $user = $student->user;

                    if ($user === null || ! str_ends_with($user->email, '.local')) {
                        continue;
                    }

                    if (! User::query()->where('email', $student->email)->whereKeyNot($user->id)->exists()) {
                        $user->email = $student->email;
                        $user->saveQuietly();
                    }
                }
            });
    }

    private function linkDomainUsers(University $university): void
    {
        $roleSlugs = $this->roleSlugsByUser($university);

        User::query()
            ->where(fn ($query) => $query
                ->where('email', 'like', '%@'.self::DOMAIN)
                ->orWhere('email', 'like', '%.'.self::DOMAIN))
            ->chunkById(100, function ($users) use ($university, $roleSlugs): void {
                foreach ($users as $user) {
                    $membership = UserUniversity::query()->firstOrNew(
                        ['user_id' => $user->id, 'university_id' => $university->id],
                    );

                    $membership->membership_type ??= $this->membershipTypeFor($roleSlugs[$user->id] ?? []);
                    $membership->status = MembershipStatus::Active;
                    $membership->joined_at ??= now();
                    $membership->left_at = null;
                    $membership->is_default = true;
                    $membership->save();

                    UserUniversity::query()
                        ->where('user_id', $user->id)
                        ->where('university_id', '!=', $university->id)
                        ->where('is_default', true)
                        ->update(['is_default' => false]);
                }
            });
    }

    private function ensureLecturerRecords(University $university): void
    {
        $roleIds = Role::query()->whereIn('slug', self::LECTURER_ROLES)->whereNull('university_id')->pluck('id');

        $userIds = UserRole::query()
            ->whereIn('role_id', $roleIds)
            ->where('university_id', $university->id)
            ->distinct()
            ->pluck('user_id');

        $employees = app(EmployeeService::class);

        foreach (User::query()->whereIn('id', $userIds)->orderBy('email')->get() as $user) {
            if (Employee::query()->withTrashed()->where('user_id', $user->id)->exists()) {
                continue;
            }

            // Dosen yang sudah ada dengan email sama cukup ditautkan ke akunnya.
            $lecturer = Lecturer::query()
                ->where('university_id', $university->id)
                ->where('email', $user->email)
                ->first();

            if ($lecturer !== null) {
                $employee = $employees->ensureEmployeeForLecturer($lecturer);
                $employee->user_id = $user->id;
                $employee->saveQuietly();

                continue;
            }

            $employee = new Employee([
                'university_id' => $university->id,
                'user_id' => $user->id,
                'employee_type' => EmployeeType::Lecturer,
                'name' => $user->name,
                'email' => $user->email,
                'employment_status' => EmploymentStatus::Permanent,
                'is_active' => true,
            ]);
            $employees->syncLegacyColumns($employee);
            $employee->saveQuietly();

            $lecturer = new Lecturer([
                'university_id' => $university->id,
                'employee_id' => $employee->id,
                'nidn' => $this->nextNidn($university),
                'name' => $user->name,
                'email' => $user->email,
                'lecturer_status' => LecturerStatus::Permanent,
                'is_active' => true,
            ]);
            $lecturer->saveQuietly();
        }
    }

    /**
     * @return array<string, array<int, string>> user_id => role slug di UAJ
     */
    private function roleSlugsByUser(University $university): array
    {
        return UserRole::query()
            ->join('roles', 'roles.id', '=', 'user_roles.role_id')
            ->where('user_roles.university_id', $university->id)
            ->get(['user_roles.user_id', 'roles.slug'])
            ->groupBy('user_id')
            ->map(fn ($rows) => $rows->pluck('slug')->all())
            ->all();
    }

    /**
     * @param  array<int, string>  $roleSlugs
     */
    private function membershipTypeFor(array $roleSlugs): MembershipType
    {
        return match (true) {
            in_array('university_owner', $roleSlugs, true) => MembershipType::Owner,
            in_array('university_administrator', $roleSlugs, true) => MembershipType::Admin,
            in_array('auditor', $roleSlugs, true) => MembershipType::Auditor,
            array_intersect(self::LECTURER_ROLES, $roleSlugs) !== [] => MembershipType::Lecturer,
            in_array('student', $roleSlugs, true) => MembershipType::Student,
            default => MembershipType::Staff,
        };
    }

    private function nextNidn(University $university): string
    {
        $sequence = 1;

        do {
            $nidn = sprintf('UAJDSN%04d', $sequence++);
        } while (Lecturer::query()->where('university_id', $university->id)->where('nidn', $nidn)->exists());

        return $nidn;
    }
}
