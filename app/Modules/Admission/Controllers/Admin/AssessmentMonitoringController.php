<?php

namespace Modules\Admission\Controllers\Admin;

use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Admission\Enums\AssessmentSessionStatus;
use Modules\Admission\Models\AssessmentSession;
use Modules\Admission\Services\Assessment\AssessmentMonitoringService;
use Modules\Admission\Services\Assessment\AssessmentSessionService;
use Modules\Admission\Support\PmbPermission;

/** Monitoring peserta, dashboard, review hasil, invalidasi, dan audit One Atma Talent (docs/one-atma-talent/04, 06). */
class AssessmentMonitoringController extends AdminController
{
    public function __construct(
        private readonly AssessmentMonitoringService $monitoring,
        private readonly AssessmentSessionService $sessions,
    ) {}

    /** Ringkasan dashboard. Distribusi hasil hanya bila pengguna punya izin baca hasil. */
    public function dashboard(Request $request): JsonResponse
    {
        $filters = $this->filters($request);
        $includeResults = $this->user($request)->hasPermissionTo(PmbPermission::ASSESSMENT_RESULTS_READ);

        return ApiResponse::success($this->monitoring->dashboard($filters, $includeResults));
    }

    public function sessions(Request $request): JsonResponse
    {
        $filters = $this->filters($request);
        $canSeeName = $this->user($request)->hasPermissionTo(PmbPermission::APPLICATIONS_READ);
        $query = $this->monitoring->sessionsQuery($filters, $canSeeName);

        return $this->paginate($query, $request, fn (AssessmentSession $s) => $this->monitoring->sessionRow($s, $canSeeName));
    }

    public function showSession(Request $request, AssessmentSession $session): JsonResponse
    {
        $user = $this->user($request);

        return ApiResponse::success($this->monitoring->detail(
            $session,
            canSeeName: $user->hasPermissionTo(PmbPermission::APPLICATIONS_READ),
            canSeeResponses: $user->hasPermissionTo(PmbPermission::SENSITIVE_READ),
        ));
    }

    public function review(AssessmentSession $session): JsonResponse
    {
        return ApiResponse::success($this->monitoring->review($session));
    }

    public function addNote(Request $request, AssessmentSession $session): JsonResponse
    {
        $data = $request->validate(['body' => ['required', 'string', 'min:3', 'max:2000']]);
        $this->monitoring->addNote($session, $this->user($request), $data['body']);

        return ApiResponse::success($this->monitoring->review($session->refresh()), 'Catatan review disimpan.', status: 201);
    }

    public function flag(Request $request, AssessmentSession $session): JsonResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:1000']]);
        $this->monitoring->flag($session, $this->user($request), $data['reason']);

        return ApiResponse::success($this->monitoring->review($session->refresh()), 'Sesi ditandai perlu review.');
    }

    public function unflag(Request $request, AssessmentSession $session): JsonResponse
    {
        $data = $request->validate(['note' => ['required', 'string', 'min:5', 'max:1000']]);
        $this->monitoring->unflag($session, $this->user($request), $data['note']);

        return ApiResponse::success($this->monitoring->review($session->refresh()), 'Penandaan review dibatalkan.');
    }

    public function completeReview(Request $request, AssessmentSession $session): JsonResponse
    {
        $data = $request->validate(['note' => ['nullable', 'string', 'max:1000']]);
        $this->monitoring->completeReview($session, $this->user($request), $data['note'] ?? null);

        return ApiResponse::success($this->monitoring->review($session->refresh()), 'Review selesai.');
    }

    /** Invalidasi sesi dengan alasan. Tidak ada tombol untuk mengubah jawaban atau memperpanjang waktu. */
    public function invalidate(Request $request, AssessmentSession $session): JsonResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:1000']]);
        $session = $this->monitoring->invalidate($session, $this->user($request), $data['reason']);

        return ApiResponse::success(['id' => $session->getKey(), 'status' => $session->status->value], 'Sesi diinvalidasi.');
    }

    public function publishSession(Request $request, AssessmentSession $session): JsonResponse
    {
        $session = $this->sessions->publish($session, $this->user($request));

        return ApiResponse::success(['id' => $session->getKey(), 'status' => $session->status->value, 'published_at' => $session->published_at?->toIso8601String()], 'Hasil dipublikasikan ke peserta.');
    }

    public function audit(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'auditable_type' => ['nullable', 'string', 'max:255'],
            'auditable_id' => ['nullable', 'string', 'max:64'],
            'action' => ['nullable', 'string', 'max:40'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        return $this->paginate($this->monitoring->auditQuery($filters), $request, fn ($log) => [
            'id' => $log->getKey(),
            'action' => $log->action->value,
            'auditable_type' => class_basename((string) $log->auditable_type),
            'auditable_id' => $log->auditable_id,
            'user' => $log->user?->name,
            'old_values' => $log->old_values,
            'new_values' => $log->new_values,
            'reason' => $log->reason,
            'at' => $log->created_at->toIso8601String(),
        ]);
    }

    /** @return array<string, mixed> */
    private function filters(Request $request): array
    {
        return $request->validate([
            'status' => ['nullable', Rule::in(AssessmentSessionStatus::values())],
            'review_status' => ['nullable', Rule::in(['none', 'flagged', 'reviewed'])],
            'mode' => ['nullable', Rule::in(['online'])],
            'type_id' => ['nullable', 'string', 'size:26'],
            'version_id' => ['nullable', 'string', 'size:26'],
            'period_id' => ['nullable', 'string', 'size:26'],
            'study_program_id' => ['nullable', 'string', 'size:26'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);
    }
}
