<?php

namespace Modules\Admission\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Http\ApiResponse;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Admission\Support\ScopeResolver;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Basis controller staf PMB. Daftar berpaginasi memakai bentuk respons
 * standar (data + meta current_page/per_page/total/last_page).
 */
abstract class AdminController extends Controller
{
    protected function user(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }

    /**
     * @param  callable(mixed): mixed|null  $map
     */
    protected function paginate(Builder $query, Request $request, ?callable $map = null, int $default = 25): JsonResponse
    {
        $perPage = max(1, min(100, (int) $request->integer('per_page', $default)));
        $page = $query->paginate($perPage);
        $items = $map ? $page->getCollection()->map($map)->values() : $page->getCollection()->values();

        return ApiResponse::success($items, meta: [
            'current_page' => $page->currentPage(),
            'per_page' => $page->perPage(),
            'total' => $page->total(),
            'last_page' => $page->lastPage(),
        ]);
    }

    /** Data di luar scope fakultas/prodi diperlakukan sebagai tidak ada (08 §5). */
    protected function assertProgramScope(Request $request, string $permission, ?string $studyProgramId): void
    {
        if ($studyProgramId !== null && ! app(ScopeResolver::class)->canAccessProgram($this->user($request), $permission, $studyProgramId)) {
            throw new NotFoundHttpException;
        }
    }

    /** @return array<int, string>|null */
    protected function programScope(Request $request, string $permission): ?array
    {
        return app(ScopeResolver::class)->programIdsFor($this->user($request), $permission);
    }
}
