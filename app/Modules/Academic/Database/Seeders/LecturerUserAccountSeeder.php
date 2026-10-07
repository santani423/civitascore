<?php

namespace Modules\Academic\Database\Seeders;

use App\Models\User;
use App\Support\Http\Exceptions\ConflictException;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Academic\Models\Faculty;
use Modules\Academic\Models\Lecturer;
use Modules\Academic\Services\LecturerAccountService;
use Modules\UserManagement\Models\UserRole;

/**
 * Backfills the lecturers.user_id identity link, same idea as
 * StudentUserAccountSeeder:
 *
 * 1. A dosen account the SDM seeder already tied to an employee row
 *    (employees.user_id, e.g. the demo `dosen@`/`dosenpa@<domain>`) is
 *    linked to that employee's lecturer row, so both links point at the
 *    same person.
 * 2. Every lecturer with an email but no account gets one through
 *    LecturerAccountService::provision() (password "password", forced
 *    change on first login; an existing user with that email is linked,
 *    never modified).
 * 3. Every user already holding the `lecturer` role in a university but
 *    not linked to any lecturer row there gets a lecturer row created for
 *    them, so logging in as the demo dosen shows a real "Profil Saya".
 * 4. Linked accounts are mirrored onto employees.user_id, which SDM
 *    self-service (`me/hr/*`) resolves.
 *
 * Idempotent: only touches unlinked rows/users. Run standalone with
 * `php artisan db:seed --class="Modules\\Academic\\Database\\Seeders\\LecturerUserAccountSeeder"`.
 */
class LecturerUserAccountSeeder extends Seeder
{
    public function run(LecturerAccountService $accounts): void
    {
        $this->linkFromEmployees($accounts);

        Lecturer::query()
            ->whereNull('user_id')
            ->whereNotNull('email')
            // Employee already tied to a non-dosen account (skipped in step
            // 1) — a second, email-based account would split the person
            // into two identities.
            ->whereDoesntHave('employee', fn ($query) => $query->whereNotNull('user_id'))
            ->chunkById(50, function ($lecturers) use ($accounts): void {
                foreach ($lecturers as $lecturer) {
                    try {
                        $accounts->provision($lecturer, 'password');
                    } catch (ConflictException|ValidationException) {
                        // Email belongs to a deleted user or another lecturer — leave for SDM to resolve.
                    }
                }
            });

        $this->linkRoleOnlyLecturers();

        Lecturer::query()
            ->whereNotNull('user_id')
            ->whereHas('employee', fn ($query) => $query->whereNull('user_id'))
            ->with('user')
            ->each(function (Lecturer $lecturer) use ($accounts): void {
                if ($lecturer->user !== null) {
                    $accounts->linkEmployee($lecturer, $lecturer->user);
                }
            });
    }

    private function linkFromEmployees(LecturerAccountService $accounts): void
    {
        Lecturer::query()
            ->whereNull('user_id')
            ->whereHas('employee', fn ($query) => $query->whereNotNull('user_id'))
            ->with('employee.user')
            ->each(function (Lecturer $lecturer) use ($accounts): void {
                $user = $lecturer->employee?->user;

                // Only accounts that already are a dosen in this university
                // (Dosen PA included — RANCANGAN-AKUN-DOSEN §4.7: a Dosen PA
                // holds `lecturer` + `academic_advisor`); linking must not
                // hand the `lecturer` role to e.g. a staff account.
                $isLecturer = $user !== null && UserRole::query()
                    ->join('roles', 'roles.id', '=', 'user_roles.role_id')
                    ->whereIn('roles.slug', ['lecturer', 'academic_advisor'])
                    ->where('user_roles.user_id', $user->id)
                    ->where('user_roles.university_id', $lecturer->university_id)
                    ->exists();

                if (! $isLecturer) {
                    return;
                }

                try {
                    $accounts->provision($lecturer, existing: $user);
                } catch (ConflictException) {
                    // Account already linked to another lecturer row.
                }
            });
    }

    private function linkRoleOnlyLecturers(): void
    {
        $grants = UserRole::query()
            ->join('roles', 'roles.id', '=', 'user_roles.role_id')
            ->where('roles.slug', 'lecturer')
            ->whereNotNull('user_roles.university_id')
            ->toBase()
            ->get(['user_roles.user_id', 'user_roles.university_id']);

        foreach ($grants as $grant) {
            $alreadyLinked = Lecturer::withTrashed()
                ->where('university_id', $grant->university_id)
                ->where('user_id', $grant->user_id)
                ->exists();

            $user = User::query()->whereKey($grant->user_id)->first();

            if ($alreadyLinked || $user === null) {
                continue;
            }

            // The user's email may already be on an unlinked lecturer row
            // (step 1 skipped it) — link that instead of duplicating.
            $lecturer = Lecturer::query()
                ->where('university_id', $grant->university_id)
                ->whereNull('user_id')
                ->where('email', $user->email)
                ->first();

            if ($lecturer) {
                $lecturer->update(['user_id' => $user->id]);

                continue;
            }

            Lecturer::query()->create([
                'university_id' => $grant->university_id,
                'user_id' => $user->id,
                'faculty_id' => Faculty::query()->where('university_id', $grant->university_id)->orderBy('name')->value('id'),
                'nidn' => 'DEMO'.Str::upper(Str::random(6)),
                'name' => $user->name,
                'email' => $user->email,
                'is_active' => true,
            ]);
        }
    }
}
