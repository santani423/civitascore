<?php

namespace Modules\Academic\Services;

use App\Models\User;
use App\Support\Http\Exceptions\ConflictException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Academic\Models\Lecturer;
use Modules\Auth\Actions\LogoutUserAction;
use Modules\Tenancy\Enums\MembershipStatus;
use Modules\Tenancy\Enums\MembershipType;
use Modules\Tenancy\Models\UserUniversity;
use Modules\UserManagement\Models\Role;
use Modules\UserManagement\Models\UserRole;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Owns the Lecturer <-> User identity link (lecturers.user_id): provisioning
 * a login account when SDM registers a dosen, keeping name/email/active
 * status in sync, and the account actions SDM may take (reset password,
 * activate/deactivate).
 *
 * SDM (lecturers.update) is deliberately NOT allowed to touch every linked
 * account: resetting the password of, or disabling, a user who also holds
 * e.g. university_administrator — or who belongs to another university —
 * would let SDM take over access it doesn't have itself
 * (docs/RANCANGAN-AKUN-SDM.md §11.3). Such accounts stay linked (the dosen
 * still gets the `lecturer` role) but account actions are refused; they're
 * managed by the university administrator instead.
 */
class LecturerAccountService
{
    /**
     * Roles an account may hold and still be managed from the Dosen screen.
     *
     * @var list<string>
     */
    public const MANAGEABLE_ROLES = ['lecturer', 'academic_advisor', 'employee'];

    private const LECTURER_ROLE = 'lecturer';

    public function __construct(private readonly LogoutUserAction $logout) {}

    /**
     * Creates (or, when a user with the lecturer's email already exists,
     * links) the login account, grants tenant membership + the `lecturer`
     * role, and stores lecturers.user_id. An existing account's password is
     * never changed — $password is only returned when a new account was
     * created.
     *
     * @return array{user: User, password: string|null, created: bool}
     */
    public function provision(Lecturer $lecturer, ?string $password = null, ?User $actor = null): array
    {
        if ($lecturer->user_id !== null) {
            throw new ConflictException('Dosen ini sudah memiliki akun login.');
        }

        if (! $lecturer->email) {
            throw ValidationException::withMessages([
                'email' => ['Email dosen wajib diisi sebelum membuat akun login.'],
            ]);
        }

        return DB::transaction(function () use ($lecturer, $password, $actor): array {
            $user = User::withTrashed()->where('email', $lecturer->email)->first();

            if ($user?->trashed()) {
                throw new ConflictException('Email ini milik akun pengguna yang sudah dihapus. Hubungi administrator universitas untuk memulihkannya.');
            }

            if ($user && Lecturer::query()->where('user_id', $user->id)->exists()) {
                throw new ConflictException('Email ini sudah tertaut ke data dosen lain.');
            }

            $plainPassword = null;
            $created = $user === null;

            if ($user === null) {
                $plainPassword = $password ?? $this->generatePassword();

                $user = new User([
                    'name' => $lecturer->name,
                    'email' => $lecturer->email,
                    'password' => $plainPassword,
                    'is_active' => $lecturer->is_active,
                    'must_change_password' => true,
                ]);
                $user->forceFill(['email_verified_at' => now()])->save();
            } elseif ($this->isManageable($user, $lecturer->university_id)) {
                // A previously deactivated dosen account being re-linked
                // (e.g. a deleted lecturer registered again) — bring its
                // status back in line with the lecturer record.
                $user->forceFill(['is_active' => $lecturer->is_active])->save();
            }

            $this->ensureMembership($user, $lecturer->university_id, $lecturer->is_active);
            $this->grantLecturerRole($user, $lecturer->university_id, $actor);

            $lecturer->user()->associate($user);
            $lecturer->save();

            return ['user' => $user, 'password' => $plainPassword, 'created' => $created];
        });
    }

    /**
     * Applies an SDM edit to the lecturer and mirrors name/email/is_active
     * onto the linked account so the two never drift apart.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateLecturer(Lecturer $lecturer, array $data): Lecturer
    {
        return DB::transaction(function () use ($lecturer, $data): Lecturer {
            $user = $lecturer->user;
            $manageable = $user !== null && $this->isManageable($user, $lecturer->university_id);

            if ($user && array_key_exists('email', $data) && $data['email'] !== $lecturer->email) {
                $this->guardEmailChange($user, $data['email'], $manageable);
            }

            $lecturer->update($data);

            if ($user && $manageable) {
                $user->fill(['name' => $lecturer->name, 'email' => $lecturer->email])->save();

                if ($lecturer->wasChanged('is_active')) {
                    $this->applyActiveState($user, $lecturer->university_id, $lecturer->is_active);
                }
            }

            return $lecturer;
        });
    }

    /**
     * Issues a fresh temporary password (forced change on next login) and
     * signs the account out everywhere.
     */
    public function resetPassword(Lecturer $lecturer, ?string $password = null): string
    {
        $user = $this->manageableUserOf($lecturer);
        $plainPassword = $password ?? $this->generatePassword();

        DB::transaction(function () use ($user, $plainPassword): void {
            $user->forceFill([
                'password' => $plainPassword,
                'must_change_password' => true,
            ])->save();

            $this->logout->executeAllDevices($user);
        });

        return $plainPassword;
    }

    /**
     * Activates/deactivates the login account only (the lecturer record's
     * own is_active is left alone — e.g. to suspend login temporarily).
     */
    public function setAccountActive(Lecturer $lecturer, bool $active): User
    {
        $user = $this->manageableUserOf($lecturer);

        DB::transaction(fn () => $this->applyActiveState($user, $lecturer->university_id, $active));

        return $user->refresh();
    }

    /**
     * Called before a lecturer record is (soft) deleted: revokes the
     * `lecturer` role in this tenant, disables the account if SDM manages it,
     * and clears the link so the account can later be linked again.
     */
    public function detach(Lecturer $lecturer): void
    {
        $user = $lecturer->user;

        if ($user === null) {
            return;
        }

        DB::transaction(function () use ($lecturer, $user): void {
            if ($this->isManageable($user, $lecturer->university_id)) {
                $this->applyActiveState($user, $lecturer->university_id, false);
            }

            $this->revokeLecturerRole($user, $lecturer->university_id);

            $lecturer->user()->dissociate();
            $lecturer->save();
        });
    }

    /**
     * True when every role the user holds (in any tenant) is one SDM may
     * manage and the user belongs to no other university.
     */
    public function isManageable(User $user, string $universityId): bool
    {
        $hasForeignRole = UserRole::query()
            ->join('roles', 'roles.id', '=', 'user_roles.role_id')
            ->where('user_roles.user_id', $user->id)
            ->whereNotIn('roles.slug', self::MANAGEABLE_ROLES)
            ->exists();

        if ($hasForeignRole) {
            return false;
        }

        return ! UserUniversity::query()
            ->where('user_id', $user->id)
            ->where('university_id', '!=', $universityId)
            ->exists();
    }

    private function manageableUserOf(Lecturer $lecturer): User
    {
        $user = $lecturer->user;

        if ($user === null) {
            throw new ConflictException('Dosen ini belum memiliki akun login.');
        }

        if (! $this->isManageable($user, $lecturer->university_id)) {
            throw new AccessDeniedHttpException(
                'Akun ini juga memiliki role lain atau terdaftar di universitas lain, sehingga hanya dapat dikelola oleh Administrator Universitas.',
            );
        }

        return $user;
    }

    private function guardEmailChange(User $user, ?string $email, bool $manageable): void
    {
        if (! $email) {
            throw ValidationException::withMessages([
                'email' => ['Email tidak boleh dikosongkan selama dosen memiliki akun login.'],
            ]);
        }

        if (! $manageable) {
            throw ValidationException::withMessages([
                'email' => ['Email akun ini hanya dapat diubah oleh Administrator Universitas karena akun memiliki role lain.'],
            ]);
        }

        if (User::withTrashed()->where('email', $email)->whereKeyNot($user->id)->exists()) {
            throw ValidationException::withMessages([
                'email' => ['Email ini sudah dipakai akun pengguna lain.'],
            ]);
        }
    }

    private function applyActiveState(User $user, string $universityId, bool $active): void
    {
        $user->forceFill(['is_active' => $active])->save();

        UserUniversity::query()
            ->where('user_id', $user->id)
            ->where('university_id', $universityId)
            ->update(['status' => $active ? MembershipStatus::Active : MembershipStatus::Inactive]);

        if (! $active) {
            $this->logout->executeAllDevices($user);
        }
    }

    private function ensureMembership(User $user, string $universityId, bool $active): void
    {
        $membership = UserUniversity::query()
            ->where('user_id', $user->id)
            ->where('university_id', $universityId);

        if ($membership->exists()) {
            if ($active && $this->isManageable($user, $universityId)) {
                $membership->update(['status' => MembershipStatus::Active]);
            }

            return;
        }

        UserUniversity::query()->create([
            'user_id' => $user->id,
            'university_id' => $universityId,
            'membership_type' => MembershipType::Lecturer,
            'status' => $active ? MembershipStatus::Active : MembershipStatus::Inactive,
            'joined_at' => now(),
            'is_default' => ! UserUniversity::query()->where('user_id', $user->id)->exists(),
        ]);
    }

    private function grantLecturerRole(User $user, string $universityId, ?User $actor): void
    {
        $role = $this->lecturerRole();

        UserRole::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'role_id' => $role->id,
                'university_id' => $universityId,
                'scope_type' => null,
                'scope_id' => null,
            ],
            ['assigned_by' => $actor?->id, 'assigned_at' => now()],
        );
    }

    private function revokeLecturerRole(User $user, string $universityId): void
    {
        // Deleted one by one (not a bulk query delete) so UserRole's
        // `deleted` hook fires and flushes the permission cache — see
        // RevokeRoleAction.
        UserRole::query()
            ->where('user_id', $user->id)
            ->where('role_id', $this->lecturerRole()->id)
            ->where('university_id', $universityId)
            ->get()
            ->each(fn (UserRole $userRole) => $userRole->delete());
    }

    private function lecturerRole(): Role
    {
        // Same global template OrganizationalRoleSeeder maintains (it
        // updateOrCreate()s by slug + null university and syncs the
        // permissions), created here only if the seeder hasn't run yet.
        return Role::query()->firstOrCreate(
            ['slug' => self::LECTURER_ROLE, 'university_id' => null],
            ['name' => 'Dosen', 'description' => 'Role Dosen.', 'is_system' => true],
        );
    }

    private function generatePassword(): string
    {
        return Str::password(10, symbols: false);
    }
}
