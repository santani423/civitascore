<?php

namespace Modules\Academic\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Academic\Enums\StudentStatus;
use Modules\Academic\Models\Student;
use Modules\Tenancy\Enums\MembershipStatus;
use Modules\Tenancy\Enums\MembershipType;
use Modules\Tenancy\Models\UserUniversity;
use Modules\UserManagement\Models\Role;
use Modules\UserManagement\Models\UserRole;

/**
 * Backfills the students.user_id identity link (see the migration in this
 * module) for every Student row that doesn't have one yet, so each
 * mahasiswa gets their own login account instead of everyone sharing the
 * single per-university demo "mahasiswa@..." account from
 * DemoUniversitiesSeeder.
 *
 * Idempotent and safe to re-run: only touches students where user_id is
 * still null, and reuses (never mutates) an already-existing User matched
 * by email — this only ever grants the `student` role/membership on top,
 * it never changes an existing account's password or active flag.
 *
 * Runs as part of the main DatabaseSeeder chain (after
 * DemoUniversitiesSeeder), so a fresh `migrate:fresh --seed` always leaves
 * every student linked. Can also be run standalone to repair existing data:
 * `php artisan db:seed --class="Modules\\Academic\\Database\\Seeders\\StudentUserAccountSeeder"`.
 */
class StudentUserAccountSeeder extends Seeder
{
    public function run(): void
    {
        $studentRole = Role::query()->where('slug', 'student')->whereNull('university_id')->first();

        Student::query()
            ->whereNull('user_id')
            ->with('university')
            ->chunkById(200, function ($students) use ($studentRole): void {
                foreach ($students as $student) {
                    $this->linkStudent($student, $studentRole);
                }
            });
    }

    private function linkStudent(Student $student, ?Role $studentRole): void
    {
        $email = $student->email ?: $this->fallbackEmail($student);

        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => $student->name,
                'password' => $student->nim,
                'email_verified_at' => now(),
                'is_active' => ! in_array($student->status, [StudentStatus::Inactive, StudentStatus::DroppedOut], true),
            ],
        );

        $isFirstMembership = ! UserUniversity::query()->where('user_id', $user->id)->exists();

        UserUniversity::query()->updateOrCreate(
            ['user_id' => $user->id, 'university_id' => $student->university_id],
            [
                'membership_type' => MembershipType::Student,
                'status' => MembershipStatus::Active,
                'joined_at' => $student->enrolled_at ?? now(),
                'is_default' => $isFirstMembership,
            ],
        );

        if ($studentRole) {
            UserRole::query()->firstOrCreate(
                [
                    'user_id' => $user->id,
                    'role_id' => $studentRole->id,
                    'university_id' => $student->university_id,
                    'scope_type' => null,
                    'scope_id' => null,
                ],
                ['assigned_at' => now()],
            );
        }

        $student->update(['user_id' => $user->id]);
    }

    private function fallbackEmail(Student $student): string
    {
        $domain = Str::slug($student->university?->code ?? 'university');

        return "{$student->nim}@student.{$domain}.local";
    }
}
