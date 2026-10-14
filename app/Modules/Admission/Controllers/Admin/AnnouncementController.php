<?php

namespace Modules\Admission\Controllers\Admin;

use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Admission\Models\AdmissionDecision;
use Modules\Admission\Models\AnnouncementBatch;
use Modules\Admission\Models\ChangeRequest;
use Modules\Admission\Models\Period;
use Modules\Admission\Models\Quota;
use Modules\Admission\Models\SelectionRun;
use Modules\Admission\Services\Announcement\DecisionService;
use Modules\Admission\Services\Announcement\PublicationService;
use Modules\Admission\Support\PmbFileStorage;
use Modules\Admission\Support\PmbPermission;
use Modules\Admission\Support\ScopeResolver;

/** Pengumuman, keputusan, koreksi, kursi & cadangan (FR-ANN, ADM-55..62). */
class AnnouncementController extends AdminController
{
    public function __construct(private readonly PublicationService $publication, private readonly DecisionService $decisions) {}

    public function batches(Request $request): JsonResponse
    {
        $query = AnnouncementBatch::query()->with(['run.wavePath.wave:id,code,name', 'run.wavePath.admissionPath:id,code,name'])
            ->when($request->filled('status'), fn ($q) => $q->whereIn('status', explode(',', $request->string('status'))))
            ->orderByDesc('scheduled_publish_at');

        return $this->paginate($query, $request, fn (AnnouncementBatch $b) => [
            'id' => $b->getKey(), 'status' => $b->status->value, 'selection_run_id' => $b->selection_run_id,
            'wave' => $b->run?->wavePath?->wave?->name, 'path' => $b->run?->wavePath?->admissionPath?->name,
            'scheduled_publish_at' => $b->scheduled_publish_at?->toIso8601String(), 'published_at' => $b->published_at?->toIso8601String(),
            'decisions_count' => $b->decisions_count, 'failure_reason' => $b->failure_reason,
        ]);
    }

    public function scheduleBatch(Request $request, SelectionRun $selectionRun): JsonResponse
    {
        $data = $request->validate(['publish_at' => ['required', 'date', 'after:now']]);
        $batch = $this->publication->scheduleNew($selectionRun, $data['publish_at'], $this->user($request));

        return ApiResponse::success($batch, 'Pengumuman dijadwalkan.', status: 201);
    }

    /** Ubah jadwal; mempercepat dari jadwal gelombang memerlukan persetujuan (hasil ChangeRequest). */
    public function rescheduleBatch(Request $request, AnnouncementBatch $announcementBatch): JsonResponse
    {
        $data = $request->validate(['publish_at' => ['required', 'date', 'after:now'], 'reason' => ['required', 'string', 'min:5', 'max:500']]);
        $result = $this->publication->reschedule($announcementBatch, $data['publish_at'], $data['reason'], $this->user($request));

        return $result instanceof ChangeRequest
            ? ApiResponse::success(['change_request_id' => $result->getKey(), 'status' => $result->status->value], 'Percepatan pengumuman diajukan untuk persetujuan.', status: 202)
            : ApiResponse::success($result, 'Jadwal pengumuman diubah.');
    }

    public function cancelBatch(Request $request, AnnouncementBatch $announcementBatch): JsonResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:500']]);

        return ApiResponse::success($this->publication->cancel($announcementBatch, $data['reason'], $this->user($request)), 'Jadwal pengumuman dibatalkan.');
    }

    public function decisions(Request $request, ScopeResolver $scope): JsonResponse
    {
        $query = AdmissionDecision::query()->where('is_current', true)
            ->with(['application:id,registration_number,applicant_account_id,period_id,wave_id,admission_path_id', 'application.account:id,name,email', 'studyProgram:id,name', 'studySystem:id,name', 'letter:id,decision_id,letter_number,status', 'application.reRegistration:id,application_id,status'])
            ->when($request->filled('period_id'), fn ($q) => $q->whereHas('application', fn ($a) => $a->where('period_id', $request->string('period_id'))))
            ->when($request->filled('wave_id'), fn ($q) => $q->whereHas('application', fn ($a) => $a->where('wave_id', $request->string('wave_id'))))
            ->when($request->filled('decision'), fn ($q) => $q->where('decision', $request->string('decision')))
            ->when($request->filled('study_program_id'), fn ($q) => $q->where('study_program_id', $request->string('study_program_id')))
            ->when($request->filled('search'), fn ($q) => $q->whereHas('application', fn ($a) => $a->where('registration_number', 'like', '%'.$request->string('search').'%')
                ->orWhereHas('account', fn ($ac) => $ac->where('name', 'like', '%'.$request->string('search').'%'))))
            ->orderByDesc('published_at');

        // Keputusan belum terbit hanya untuk pemegang izin hasil tak terbit (J.3).
        if (! $this->user($request)->hasPermissionTo(PmbPermission::RESULTS_READ_UNPUBLISHED)) {
            $query->published();
        } elseif ($request->filled('publication_status')) {
            $query->where('publication_status', $request->string('publication_status'));
        }

        $scope->constrainApplications($query, $this->user($request), PmbPermission::APPLICATIONS_READ, 'application_id');

        return $this->paginate($query, $request, fn (AdmissionDecision $d) => [
            'id' => $d->getKey(), 'application_id' => $d->application_id,
            'registration_number' => $d->application?->registration_number, 'name' => $d->application?->account?->name, 'email' => $d->application?->account?->email,
            'decision' => $d->decision->value, 'version' => $d->decision_version, 'source' => $d->source?->value ?? $d->source,
            'program' => $d->studyProgram?->name, 'study_system' => $d->studySystem?->name, 'quota_id' => $d->quota_id,
            'waitlist_rank' => $d->waitlist_rank, 'publication_status' => $d->publication_status->value, 'published_at' => $d->published_at?->toIso8601String(),
            'letter' => $d->letter ? ['number' => $d->letter->letter_number, 'status' => $d->letter->status->value] : null,
            're_registration_status' => $d->application?->reRegistration?->status?->value,
            'scholarship_award' => $d->scholarship_award,
        ]);
    }

    public function scholarshipAward(Request $request, AdmissionDecision $admissionDecision): JsonResponse
    {
        $data = $request->validate([
            'scholarship_id' => ['nullable', 'string', 'size:26'],
            'percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'note' => ['required', 'string', 'min:5', 'max:500'],
        ]);
        $this->assertApplicationScope($request, PmbPermission::SELECTION_MANAGE, $admissionDecision->application_id);
        $decision = $this->decisions->setScholarshipAward($admissionDecision, $data['scholarship_id'] ?? null, (float) $data['percent'], $data['note'], $this->user($request));

        return ApiResponse::success(['scholarship_award' => $decision->scholarship_award], 'Beasiswa jalur masuk disimpan.');
    }

    public function requestCorrection(Request $request, PmbFileStorage $storage): JsonResponse
    {
        $data = $request->validate([
            'application_ids' => ['required', 'array', 'min:1', 'max:500'],
            'application_ids.*' => ['string', 'size:26'],
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
            'proposed' => ['nullable', 'array'],
            'proposed.*.decision' => ['required', 'in:accepted,waitlisted,rejected,withdrawn'],
            'proposed.*.quota_id' => ['nullable', 'string', 'size:26'],
            'evidence' => ['nullable', 'file', 'max:5120'],
        ]);
        $evidenceId = $request->hasFile('evidence') ? $storage->store($request->file('evidence'), ['image/jpeg', 'image/png', 'application/pdf'], 5120, ['evidence'], uploadedByUserId: (string) $this->user($request)->getKey())->getKey() : null;
        $change = $this->decisions->requestCorrection($data['application_ids'], $data['reason'], $evidenceId, (array) ($data['proposed'] ?? []), $this->user($request));

        return ApiResponse::success(['change_request_id' => $change->getKey(), 'status' => $change->status->value], 'Koreksi hasil diajukan untuk persetujuan.', status: 201);
    }

    public function changeRequests(Request $request): JsonResponse
    {
        $query = ChangeRequest::query()->with('requester:id,name')
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->when($request->filled('status'), fn ($q) => $q->whereIn('status', explode(',', $request->string('status'))))
            ->orderByDesc('created_at');

        return $this->paginate($query, $request, fn (ChangeRequest $c) => [
            'id' => $c->getKey(), 'type' => $c->type->value, 'status' => $c->status->value, 'reason' => $c->reason, 'payload' => $c->payload,
            'requested_by' => $c->requester?->name, 'approval' => $this->approvalSummary($c->approval_request_id),
            'result_note' => $c->result_note, 'decided_at' => $c->decided_at?->toIso8601String(), 'applied_at' => $c->applied_at?->toIso8601String(),
            'created_at' => $c->created_at?->toIso8601String(),
        ]);
    }

    public function seats(Period $period): JsonResponse
    {
        return ApiResponse::success($this->decisions->seatOverview((string) $period->getKey()));
    }

    public function promote(Request $request, Quota $quota): JsonResponse
    {
        $data = $request->validate(['count' => ['required', 'integer', 'min:1', 'max:500'], 'deadline' => ['nullable', 'date', 'after:now']]);

        return ApiResponse::success($this->decisions->promoteWaitlist($quota, (int) $data['count'], $data['deadline'] ?? null, $this->user($request)), 'Cadangan dipromosikan.');
    }
}
