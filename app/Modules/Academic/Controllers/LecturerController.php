<?php

namespace Modules\Academic\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use App\Support\Http\ListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Academic\Models\Faculty;
use Modules\Academic\Models\Lecturer;
use Modules\Academic\Requests\StoreLecturerRequest;
use Modules\Academic\Requests\UpdateLecturerRequest;
use Modules\Academic\Resources\LecturerResource;
use Modules\Academic\Services\LecturerAccountService;

class LecturerController extends Controller
{
    public function __construct(private readonly LecturerAccountService $accounts) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Lecturer::class);

        $query = Lecturer::query()->with(['faculty', 'user'])->orderBy('name');

        // Not a column, so applied manually rather than through ListQuery's
        // `filterable` (same approach as StudentController's class filter).
        $hasAccount = $request->input('filter.has_account');

        if ($hasAccount !== null && $hasAccount !== '') {
            filter_var($hasAccount, FILTER_VALIDATE_BOOLEAN)
                ? $query->whereNotNull('user_id')
                : $query->whereNull('user_id');
        }

        $paginator = ListQuery::paginate(
            query: $query,
            request: $request,
            searchable: ['name', 'nidn', 'nip', 'email'],
            filterable: ['faculty_id', 'is_active', 'employment_status', 'functional_rank'],
            sortable: ['name', 'nidn', 'created_at'],
        );

        return ApiResponse::paginated(LecturerResource::collection($paginator));
    }

    public function show(Lecturer $lecturer): JsonResponse
    {
        $this->authorize('view', $lecturer);

        return ApiResponse::success($this->detailResource($lecturer));
    }

    public function store(StoreLecturerRequest $request): JsonResponse
    {
        $this->authorize('create', Lecturer::class);

        $data = $request->validated();
        $createAccount = (bool) ($data['create_account'] ?? true);
        $password = $data['password'] ?? null;
        unset($data['create_account'], $data['password']);

        // Scoped findOrFail (not Rule::exists in the Request) so a
        // faculty_id belonging to another tenant reads as "not found"
        // rather than leaking cross-tenant existence — same reasoning as
        // StoreStudentRequest/ExamService::createExam().
        if (! empty($data['faculty_id'])) {
            Faculty::query()->findOrFail($data['faculty_id']);
        }

        $credentials = null;

        $lecturer = DB::transaction(function () use ($data, $createAccount, $password, $request, &$credentials): Lecturer {
            // A previously deleted lecturer with the same NIDN is restored
            // rather than duplicated — the (university_id, nidn) unique
            // index still covers soft-deleted rows anyway.
            $lecturer = Lecturer::onlyTrashed()->where('nidn', $data['nidn'])->first();

            if ($lecturer) {
                $lecturer->restore();
                $lecturer->update($data);
            } else {
                $lecturer = Lecturer::query()->create($data);
            }

            if ($createAccount) {
                $result = $this->accounts->provision($lecturer, $password, $request->user());
                $credentials = $this->credentialsMeta($result['user']->email, $result['password'], $result['created']);
            }

            return $lecturer;
        });

        return ApiResponse::success(
            $this->detailResource($lecturer),
            $credentials === null
                ? 'Dosen berhasil ditambahkan.'
                : 'Dosen dan akun login berhasil ditambahkan.',
            meta: $credentials === null ? [] : ['credentials' => $credentials],
            status: 201,
        );
    }

    public function update(UpdateLecturerRequest $request, Lecturer $lecturer): JsonResponse
    {
        $this->authorize('update', $lecturer);

        $data = $request->validated();

        if (! empty($data['faculty_id'])) {
            Faculty::query()->findOrFail($data['faculty_id']);
        }

        $this->accounts->updateLecturer($lecturer, $data);

        return ApiResponse::success($this->detailResource($lecturer), 'Dosen berhasil diperbarui.');
    }

    /**
     * Soft delete — the lecturer's history stays intact. The linked login
     * account loses the `lecturer` role and is deactivated (when SDM
     * manages it) instead of being left usable.
     */
    public function destroy(Lecturer $lecturer): JsonResponse
    {
        $this->authorize('delete', $lecturer);

        DB::transaction(function () use ($lecturer): void {
            $this->accounts->detach($lecturer);
            $lecturer->delete();
        });

        return ApiResponse::success(null, 'Dosen berhasil dihapus.');
    }

    private function detailResource(Lecturer $lecturer): LecturerResource
    {
        $lecturer->load(['faculty', 'user']);

        $manageable = $lecturer->user
            ? $this->accounts->isManageable($lecturer->user, $lecturer->university_id)
            : null;

        return (new LecturerResource($lecturer))->withAccountManageable($manageable);
    }

    /**
     * Handed back exactly once so SDM can pass the initial password to the
     * dosen; it is never retrievable again (only reset).
     *
     * @return array{email: string, password: string|null, account_created: bool}
     */
    private function credentialsMeta(string $email, ?string $password, bool $created): array
    {
        return ['email' => $email, 'password' => $password, 'account_created' => $created];
    }
}
