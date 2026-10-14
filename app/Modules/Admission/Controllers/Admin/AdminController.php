<?php

namespace Modules\Admission\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Http\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Admission\Models\Application;
use Modules\Admission\Support\ScopeResolver;
use Modules\ApprovalWorkflow\Models\ApprovalRequest;
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

    /** Aplikasi di luar scope prodi staf → 404 (bukan 403, agar keberadaan tidak bocor). */
    protected function assertApplicationScope(Request $request, string $permission, Application|string $application): void
    {
        $id = is_string($application) ? $application : (string) $application->getKey();
        $visible = app(ScopeResolver::class)->constrainApplications(Application::query()->whereKey($id), $this->user($request), $permission)->exists();

        if (! $visible) {
            throw new NotFoundHttpException;
        }
    }

    /**
     * Ringkasan approval (mesin ApprovalWorkflow) untuk ditampilkan; aksi
     * approve/reject/return memakai endpoint /approval-request-steps yang ada.
     *
     * @return array<string, mixed>|null
     */
    protected function approvalSummary(?string $approvalRequestId): ?array
    {
        if ($approvalRequestId === null) {
            return null;
        }

        $approval = ApprovalRequest::query()->with(['steps' => fn ($q) => $q->orderBy('sequence'), 'histories'])->find($approvalRequestId);

        if ($approval === null) {
            return null;
        }

        $approverNames = User::query()->whereIn('id', $approval->steps->pluck('assigned_approver_user_id')->filter())->pluck('name', 'id');

        return [
            'id' => $approval->getKey(),
            'status' => $approval->status?->value ?? $approval->status,
            'current_step_id' => $approval->current_step_id,
            'submitted_at' => $approval->submitted_at?->toIso8601String(),
            'completed_at' => $approval->completed_at?->toIso8601String(),
            'steps' => $approval->steps->map(fn ($s) => [
                'id' => $s->getKey(), 'sequence' => $s->sequence, 'status' => $s->status?->value ?? $s->status,
                'approver' => $s->assigned_approver_user_id ? ($approverNames[$s->assigned_approver_user_id] ?? null) : null,
                'acted_at' => $s->acted_at?->toIso8601String(),
            ])->values(),
        ];
    }

    /** @return array<int, string>|null */
    protected function programScope(Request $request, string $permission): ?array
    {
        return app(ScopeResolver::class)->programIdsFor($this->user($request), $permission);
    }
}
