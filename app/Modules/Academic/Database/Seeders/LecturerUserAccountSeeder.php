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
 * 1. Every lecturer with an email but no account gets one through
 *    LecturerAccountService::provision() (password "password", forced
 *    change on first login; an existing user with that email is linked,
 *    never modified).
 * 2. Every user already holding the `lecturer` role in a university but
 *    not linked to any lecturer row there (the demo `dosen@<domain>`
 *    accounts) gets a lecturer row created for them, so logging in as the
 *    demo dosen shows a real "Profil Saya".
 *
 * Idempotent: only touches unlinked rows/users. Run standalone with
 * `php artisan db:seed --class="Modules\\Academic\\Database\\Seeders\\LecturerUserAccountSeeder"`.
 */
class LecturerUserAccountSeeder extends Seeder
{
    public function run(LecturerAccountService $accounts): void
    {
        Lecturer::query()
            ->whereNull('user_id')
            ->whereNotNull('email')
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
