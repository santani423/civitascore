<?php

namespace Modules\Admission\Support;

use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Modules\Academic\Models\StudyProgram;

/**
 * Scope data staf ber-scope fakultas/prodi (08 §5 butir 2–3): bila
 * permission didapat dari role grant tanpa scope → tanpa batas (null);
 * bila hanya dari grant ber-scope → daftar study_program_id yang boleh.
 * Data di luar scope tidak pernah terambil (query scope, bukan sembunyi UI).
 */
class ScopeResolver
{
    /** @var array<string, array<int, string>|null> */
    private array $memo = [];

    public function __construct(private readonly TenantContext $tenant) {}

    /** @return array<int, string>|null null = seluruh tenant */
    public function programIdsFor(User $user, string $permission): ?array
    {
        $key = $user->getKey().'|'.$permission.'|'.$this->tenant->universityId();

        if (array_key_exists($key, $this->memo)) {
            return $this->memo[$key];
        }

        if ($user->hasRole('super_admin')) {
            return $this->memo[$key] = null;
        }

        $universityId = $this->tenant->universityId();
        $grants = DB::table('user_roles')
            ->join('role_permissions', 'role_permissions.role_id', '=', 'user_roles.role_id')
            ->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
            ->where('user_roles.user_id', $user->getKey())
            ->where('permissions.slug', $permission)
            ->where(fn ($q) => $q->whereNull('user_roles.expires_at')->orWhere('user_roles.expires_at', '>', now()))
            ->where(fn ($q) => $q->whereNull('user_roles.university_id')->orWhere('user_roles.university_id', $universityId))
            ->get(['user_roles.scope_type', 'user_roles.scope_id']);

        if ($grants->contains(fn ($grant) => $grant->scope_type === null)) {
            return $this->memo[$key] = null;
        }

        $programIds = $grants->where('scope_type', 'study_program')->pluck('scope_id')->all();
        $facultyIds = $grants->where('scope_type', 'faculty')->pluck('scope_id')->all();

        if ($facultyIds !== []) {
            $programIds = [...$programIds, ...StudyProgram::query()->whereIn('faculty_id', $facultyIds)->pluck('id')->all()];
        }

        return $this->memo[$key] = array_values(array_unique(array_map('strval', $programIds)));
    }

    /**
     * Membatasi query aplikasi ke yang memilih prodi dalam scope (pilihan mana pun).
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public function constrainApplications(Builder $query, User $user, string $permission, string $applicationColumn = 'id'): Builder
    {
        $programIds = $this->programIdsFor($user, $permission);

        if ($programIds === null) {
            return $query;
        }

        return $query->whereIn($applicationColumn, function ($sub) use ($programIds): void {
            $sub->select('application_id')->from('pmb_application_choices')->whereIn('study_program_id', $programIds === [] ? ['-'] : $programIds);
        });
    }

    public function canAccessProgram(User $user, string $permission, string $studyProgramId): bool
    {
        $programIds = $this->programIdsFor($user, $permission);

        return $programIds === null || in_array($studyProgramId, $programIds, true);
    }
}
