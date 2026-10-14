<?php

namespace Modules\Admission\Controllers\Admin;

use App\Support\Http\ApiResponse;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Admission\Enums\AttendanceStatus;
use Modules\Admission\Enums\ExaminerRole;
use Modules\Admission\Enums\ExamMode;
use Modules\Admission\Enums\RescheduleRequestStatus;
use Modules\Admission\Models\Application;
use Modules\Admission\Models\CbtQuestion;
use Modules\Admission\Models\ExamParticipant;
use Modules\Admission\Models\ExamRescheduleRequest;
use Modules\Admission\Models\ExamRoom;
use Modules\Admission\Models\ExamSchedule;
use Modules\Admission\Models\ExamSession;
use Modules\Admission\Models\ExamVenue;
use Modules\Admission\Models\SelectionScore;
use Modules\Admission\Models\Wave;
use Modules\Admission\Services\Exam\CbtService;
use Modules\Admission\Services\Exam\ExamService;
use Modules\Admission\Services\Selection\ScoreService;
use Modules\Admission\Support\PdfRenderer;
use Modules\Admission\Support\PmbFileStorage;
use Modules\Admission\Support\PmbPermission;
use Modules\Tenancy\Models\University;

/** Ujian: lokasi, jadwal, sesi, penempatan, presensi, nilai, CBT (FR-EXM, ADM-40..46). */
class ExamController extends AdminController
{
    public function __construct(private readonly ExamService $exams) {}

    // ------------------------------------------------------------------ Lokasi & ruang

    public function venues(): JsonResponse
    {
        return ApiResponse::success(ExamVenue::query()->with(['rooms' => fn ($q) => $q->orderBy('code')])->orderBy('name')->get());
    }

    public function storeVenue(Request $request): JsonResponse
    {
        return ApiResponse::success($this->exams->saveVenue(null, $this->validateVenue($request, true)), 'Lokasi dibuat.', status: 201);
    }

    public function updateVenue(Request $request, ExamVenue $examVenue): JsonResponse
    {
        return ApiResponse::success($this->exams->saveVenue($examVenue, $this->validateVenue($request, false)), 'Lokasi diperbarui.');
    }

    public function destroyVenue(ExamVenue $examVenue): JsonResponse
    {
        $this->exams->deleteVenue($examVenue);

        return ApiResponse::success(null, 'Lokasi dihapus.');
    }

    public function storeRoom(Request $request, ExamVenue $examVenue): JsonResponse
    {
        return ApiResponse::success($this->exams->saveRoom($examVenue, null, $this->validateRoom($request, true)), 'Ruang dibuat.', status: 201);
    }

    public function updateRoom(Request $request, ExamRoom $examRoom): JsonResponse
    {
        return ApiResponse::success($this->exams->saveRoom($examRoom->venue, $examRoom, $this->validateRoom($request, false)), 'Ruang diperbarui.');
    }

    public function destroyRoom(ExamRoom $examRoom): JsonResponse
    {
        $this->exams->deleteRoom($examRoom);

        return ApiResponse::success(null, 'Ruang dihapus.');
    }

    // ------------------------------------------------------------------ Jadwal

    public function schedules(Wave $wave): JsonResponse
    {
        return ApiResponse::success(ExamSchedule::query()->where('wave_id', $wave->getKey())->with('component:id,code,name,source')
            ->withCount(['sessions', 'participants'])->orderBy('exam_date')->get());
    }

    public function storeSchedule(Request $request, Wave $wave): JsonResponse
    {
        return ApiResponse::success($this->exams->saveSchedule($wave, null, $this->validateSchedule($request, true), $this->user($request)), 'Jadwal ujian dibuat.', status: 201);
    }

    public function schedule(ExamSchedule $examSchedule): JsonResponse
    {
        $examSchedule->load(['wave:id,code,name,exam_starts_on,exam_ends_on,is_ods', 'component:id,code,name,source,score_type,raw_min,raw_max',
            'sessions' => fn ($q) => $q->orderBy('starts_at'), 'sessions.room.venue', 'sessions.examiners.user:id,name']);

        return ApiResponse::success([
            ...$examSchedule->toArray(),
            'unassigned_count' => $this->unassignedCount($examSchedule),
            'cbt_question_total' => $examSchedule->mode === ExamMode::Cbt ? $examSchedule->cbtQuestions()->count() : null,
        ]);
    }

    public function updateSchedule(Request $request, ExamSchedule $examSchedule): JsonResponse
    {
        return ApiResponse::success($this->exams->saveSchedule($examSchedule->wave, $examSchedule, $this->validateSchedule($request, false), $this->user($request)), 'Jadwal ujian diperbarui.');
    }

    public function publishSchedule(Request $request, ExamSchedule $examSchedule): JsonResponse
    {
        return ApiResponse::success($this->exams->publishSchedule($examSchedule, $this->user($request)), 'Jadwal dipublikasikan; kartu peserta diterbitkan.');
    }

    public function cancelSchedule(Request $request, ExamSchedule $examSchedule): JsonResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:500']]);

        return ApiResponse::success($this->exams->cancelSchedule($examSchedule, $data['reason'], $this->user($request)), 'Jadwal dibatalkan.');
    }

    // ------------------------------------------------------------------ Sesi & penempatan

    public function storeSession(Request $request, ExamSchedule $examSchedule): JsonResponse
    {
        return ApiResponse::success($this->exams->saveSession($examSchedule, null, $this->validateSession($request, true)), 'Sesi dibuat.', status: 201);
    }

    public function updateSession(Request $request, ExamSession $examSession): JsonResponse
    {
        $data = $this->validateSession($request, false);

        return ApiResponse::success($this->exams->saveSession($examSession->schedule, $examSession, $data, $data['reason'] ?? null), 'Sesi diperbarui.');
    }

    public function destroySession(ExamSession $examSession): JsonResponse
    {
        $this->exams->deleteSession($examSession);

        return ApiResponse::success(null, 'Sesi dihapus.');
    }

    public function autoAssign(Request $request, ExamSchedule $examSchedule): JsonResponse
    {
        $data = $request->validate(['strategy' => ['nullable', Rule::in(['registration_number', 'study_program', 'accessibility'])], 'only_unassigned' => ['nullable', 'boolean']]);

        return ApiResponse::success($this->exams->autoAssign($examSchedule, $data['strategy'] ?? 'registration_number', (bool) ($data['only_unassigned'] ?? true)), 'Penempatan otomatis selesai.');
    }

    public function autoExaminers(Request $request, ExamSchedule $examSchedule): JsonResponse
    {
        $data = $request->validate([
            'user_ids' => ['required', 'array', 'min:1', 'max:200'],
            'user_ids.*' => ['string'],
            'role' => ['required', Rule::in(ExaminerRole::values())],
            'per_session' => ['nullable', 'integer', 'min:1', 'max:10'],
        ]);

        return ApiResponse::success(['assigned' => $this->exams->autoAssignExaminers($examSchedule, $data['user_ids'], $data['role'], (int) ($data['per_session'] ?? 1))], 'Penguji/pengawas ditugaskan.');
    }

    public function move(Request $request, ExamParticipant $examParticipant): JsonResponse
    {
        $data = $request->validate(['target_session_id' => ['required', 'string', 'size:26'], 'reason' => ['required', 'string', 'min:5', 'max:500']]);
        $participant = $this->exams->moveParticipant($examParticipant, $data['target_session_id'], $data['reason'], $this->user($request));

        return ApiResponse::success($participant->only(['id', 'session_id', 'participant_number']), 'Peserta dipindahkan.');
    }

    public function examiners(Request $request, ExamSession $examSession): JsonResponse
    {
        $data = $request->validate([
            'examiners' => ['present', 'array', 'max:20'],
            'examiners.*.user_id' => ['required', 'string', Rule::exists('users', 'id')],
            'examiners.*.role' => ['required', Rule::in(ExaminerRole::values())],
        ]);

        return ApiResponse::success($this->exams->setExaminers($examSession, $data['examiners'])->load('examiners.user:id,name')->examiners, 'Petugas sesi disimpan.');
    }

    public function bulkReschedule(Request $request, ExamSession $examSession): JsonResponse
    {
        $data = $request->validate(['target_session_id' => ['required', 'string', 'size:26'], 'reason' => ['required', 'string', 'min:5', 'max:500']]);
        $target = ExamSession::query()->findOrFail($data['target_session_id']);

        return ApiResponse::success(['moved' => $this->exams->bulkReschedule($examSession, $target, $data['reason'], $this->user($request))], 'Peserta sesi dipindahkan.');
    }

    public function rescheduleRequests(Request $request): JsonResponse
    {
        $query = ExamRescheduleRequest::query()->with(['participant.application.account:id,name', 'participant.session:id,code,starts_at,schedule_id', 'participant.schedule:id,title'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')), fn ($q) => $q->where('status', RescheduleRequestStatus::Pending->value))
            ->orderBy('created_at');
        $storage = app(PmbFileStorage::class);

        return $this->paginate($query, $request, fn (ExamRescheduleRequest $r) => [
            'id' => $r->getKey(), 'status' => $r->status->value, 'reason' => $r->reason,
            'participant_number' => $r->participant?->participant_number, 'name' => $r->participant?->application?->account?->name,
            'schedule' => $r->participant?->schedule?->title, 'schedule_id' => $r->participant?->schedule_id,
            'current_session' => $r->participant?->session?->code, 'current_starts_at' => $r->participant?->session?->starts_at?->toIso8601String(),
            'attachment_url' => $r->attachment && $r->attachment->status->value === 'clean' ? $storage->temporaryUrl($r->attachment, 30) : null,
            'decision_note' => $r->decision_note, 'decided_at' => $r->decided_at?->toIso8601String(), 'created_at' => $r->created_at?->toIso8601String(),
        ]);
    }

    public function decideReschedule(Request $request, ExamRescheduleRequest $examRescheduleRequest): JsonResponse
    {
        $data = $request->validate([
            'approve' => ['required', 'boolean'],
            'target_session_id' => ['required_if:approve,true', 'nullable', 'string', 'size:26'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);
        $decided = $this->exams->decideReschedule($examRescheduleRequest, (bool) $data['approve'], $data['target_session_id'] ?? null, $data['note'] ?? null, $this->user($request));

        return ApiResponse::success(['id' => $decided->getKey(), 'status' => $decided->status->value], 'Permintaan diputuskan.');
    }

    // ------------------------------------------------------------------ Presensi (pengawas)

    public function session(Request $request, ExamSession $examSession): JsonResponse
    {
        $this->exams->assertSessionStaff($examSession, $this->user($request), ['proctor', 'examiner', 'interviewer']);
        $examSession->load(['schedule:id,title,mode,exam_date,selection_component_id', 'room.venue', 'examiners.user:id,name']);

        return ApiResponse::success([
            ...$examSession->toArray(),
            'participants' => $examSession->participants()->with('application.account:id,name')->orderBy('participant_number')->get()
                ->map(fn (ExamParticipant $p) => ['id' => $p->getKey(), 'participant_number' => $p->participant_number, 'name' => $p->application?->account?->name, 'attendance_status' => $p->attendance_status->value, 'checked_in_at' => $p->checked_in_at?->toIso8601String(), 'note' => $p->note]),
            'attendance_statuses' => AttendanceStatus::values(),
        ]);
    }

    public function checkIn(Request $request, ExamSession $examSession): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required_without:participant_id', 'nullable', 'string', 'max:500'],
            'participant_id' => ['required_without:token', 'nullable', 'string', 'size:26'],
            'status' => ['nullable', Rule::in(AttendanceStatus::values())],
        ]);
        $participant = $this->exams->checkIn($examSession, $data['token'] ?? null, $data['participant_id'] ?? null, $data['status'] ?? AttendanceStatus::Present->value, $this->user($request));
        $participant->load('application.account:id,name');

        return ApiResponse::success([
            'id' => $participant->getKey(), 'participant_number' => $participant->participant_number, 'name' => $participant->application?->account?->name,
            'attendance_status' => $participant->attendance_status->value, 'checked_in_at' => $participant->checked_in_at?->toIso8601String(),
        ], 'Kehadiran dicatat.');
    }

    public function attendanceSheet(Request $request, ExamSession $examSession, PdfRenderer $pdf, PmbFileStorage $storage, TenantContext $tenant): JsonResponse
    {
        $this->exams->assertSessionStaff($examSession, $this->user($request), ['proctor', 'examiner', 'interviewer']);
        $examSession->load(['schedule', 'room.venue']);
        $university = University::query()->find($tenant->universityId());
        $file = $pdf->render('admission::pdf.attendance-sheet', [
            'session' => $examSession,
            'participants' => $examSession->participants()->with('application.account')->orderBy('participant_number')->get(),
            'timezone' => $university?->timezone ?: config('app.timezone'),
            'universityName' => $university?->name ?? '',
        ], 'daftar-hadir-'.$examSession->code.'.pdf', ['attendance', (string) $examSession->getKey()]);

        return ApiResponse::success(['url' => $storage->temporaryUrl($file)]);
    }

    // ------------------------------------------------------------------ Nilai sesi (penguji)

    public function mySessions(Request $request): JsonResponse
    {
        $user = $this->user($request);

        return ApiResponse::success(ExamSession::query()->with(['schedule:id,title,mode,exam_date,selection_component_id', 'schedule.component:id,code,name,source,score_type', 'room.venue:id,name'])
            ->whereHas('examiners', fn ($q) => $q->where('user_id', $user->getKey()))
            ->withCount('participants')->orderByDesc('starts_at')->limit(200)->get()
            ->map(fn (ExamSession $s) => [
                'id' => $s->getKey(), 'code' => $s->code, 'starts_at' => $s->starts_at?->toIso8601String(), 'ends_at' => $s->ends_at?->toIso8601String(),
                'schedule' => $s->schedule?->title, 'mode' => $s->schedule?->mode?->value, 'component' => $s->schedule?->component?->name,
                'room' => $s->room ? $s->room->venue?->name.' — '.$s->room->name : null, 'participants_count' => $s->participants_count,
                'scores_locked' => $s->scores_locked_at !== null,
                'roles' => $s->examiners()->where('user_id', $user->getKey())->pluck('role'),
            ]));
    }

    public function sessionScores(Request $request, ExamSession $examSession): JsonResponse
    {
        $this->exams->assertSessionStaff($examSession, $this->user($request), ['examiner', 'interviewer']);
        $examSession->load('schedule.component');
        $component = $examSession->schedule->component;
        $isManager = $this->user($request)->hasPermissionTo(PmbPermission::EXAMS_MANAGE);
        $scores = SelectionScore::query()->where('selection_component_id', $component->getKey())
            ->whereIn('application_id', $examSession->participants()->select('application_id'))
            ->when(! $isManager, fn ($q) => $q->where(fn ($w) => $w->whereNull('examiner_user_id')->orWhere('examiner_user_id', $this->user($request)->getKey())))
            ->get()->groupBy('application_id');

        return ApiResponse::success([
            'session' => $examSession->only(['id', 'code', 'starts_at', 'scores_locked_at']),
            'component' => $component->only(['id', 'code', 'name', 'source', 'score_type', 'raw_min', 'raw_max', 'rubric']),
            'rows' => $examSession->participants()->with('application.account:id,name')->orderBy('participant_number')->get()->map(fn (ExamParticipant $p) => [
                'participant_id' => $p->getKey(), 'participant_number' => $p->participant_number, 'name' => $p->application?->account?->name,
                'attendance_status' => $p->attendance_status->value,
                'scores' => ($scores->get($p->application_id) ?? collect())->map->only(['examiner_user_id', 'raw_score', 'passed', 'rubric_scores', 'note'])->values(),
            ]),
        ]);
    }

    public function inputScores(Request $request, ExamSession $examSession, ScoreService $scores): JsonResponse
    {
        $data = $request->validate([
            'rows' => ['required', 'array', 'min:1', 'max:500'],
            'rows.*.participant_id' => ['required', 'string', 'size:26'],
            'rows.*.raw_score' => ['nullable', 'numeric'],
            'rows.*.rubric_scores' => ['nullable', 'array'],
            'rows.*.rubric_scores.*' => ['numeric'],
            'rows.*.passed' => ['nullable', 'boolean'],
            'rows.*.note' => ['nullable', 'string', 'max:255'],
        ]);

        return ApiResponse::success(['saved' => $scores->inputSessionScores($examSession, $data['rows'], $this->user($request))], 'Nilai disimpan.');
    }

    public function lockScores(Request $request, ExamSession $examSession, ScoreService $scores): JsonResponse
    {
        return ApiResponse::success($scores->lockSession($examSession, $this->user($request))->only(['id', 'scores_locked_at']), 'Nilai sesi dikunci.');
    }

    // ------------------------------------------------------------------ Bank soal CBT

    public function cbtQuestions(ExamSchedule $examSchedule): JsonResponse
    {
        return ApiResponse::success($examSchedule->cbtQuestions()->orderBy('sort_order')->get());
    }

    public function storeCbtQuestion(Request $request, ExamSchedule $examSchedule, CbtService $cbt): JsonResponse
    {
        return ApiResponse::success($cbt->saveQuestion($examSchedule, null, $this->validateQuestion($request, true)), 'Soal ditambahkan.', status: 201);
    }

    public function updateCbtQuestion(Request $request, CbtQuestion $cbtQuestion, CbtService $cbt): JsonResponse
    {
        return ApiResponse::success($cbt->saveQuestion($cbtQuestion->schedule, $cbtQuestion, $this->validateQuestion($request, false)), 'Soal diperbarui.');
    }

    public function destroyCbtQuestion(CbtQuestion $cbtQuestion, CbtService $cbt): JsonResponse
    {
        $cbt->deleteQuestion($cbtQuestion);

        return ApiResponse::success(null, 'Soal dihapus.');
    }

    // ------------------------------------------------------------------ Validasi

    private function unassignedCount(ExamSchedule $schedule): int
    {
        return Application::query()->whereIn('wave_path_id', (array) $schedule->wave_path_ids)
            ->where('verification_status', 'verified')->whereNotIn('form_status', ['withdrawn', 'void'])
            ->whereDoesntHave('examParticipations', fn ($q) => $q->where('schedule_id', $schedule->getKey()))->count();
    }

    /** @return array<string, mixed> */
    private function validateVenue(Request $request, bool $creating): array
    {
        return $request->validate([
            'name' => [$creating ? 'required' : 'sometimes', 'string', 'max:150'],
            'address' => ['nullable', 'string', 'max:1000'],
            'map_url' => ['nullable', 'url', 'max:500'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }

    /** @return array<string, mixed> */
    private function validateRoom(Request $request, bool $creating): array
    {
        $req = $creating ? 'required' : 'sometimes';

        return $request->validate([
            'code' => [$req, 'string', 'max:30'],
            'name' => [$req, 'string', 'max:150'],
            'capacity' => [$req, 'integer', 'min:1', 'max:5000'],
            'is_accessible' => ['sometimes', 'boolean'],
            'facilities' => ['nullable', 'array'],
            'facilities.*' => ['string', 'max:50'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }

    /** @return array<string, mixed> */
    private function validateSchedule(Request $request, bool $creating): array
    {
        $req = $creating ? 'required' : 'sometimes';
        $tenant = app(TenantContext::class)->universityId();

        return $request->validate([
            'selection_component_id' => [$req, 'string', Rule::exists('pmb_selection_components', 'id')->where('university_id', $tenant)],
            'wave_path_ids' => [$req, 'array', 'min:1'],
            'wave_path_ids.*' => ['string', 'size:26'],
            'mode' => [$req, Rule::in(ExamMode::values())],
            'title' => [$req, 'string', 'max:200'],
            'exam_date' => [$req, 'date'],
            'instructions' => ['nullable', 'string', 'max:5000'],
            'external_reference' => ['nullable', 'string', 'max:100'],
            'cbt_duration_minutes' => ['nullable', 'integer', 'min:5', 'max:600'],
            'cbt_question_count' => ['nullable', 'integer', 'min:1', 'max:500'],
            'cbt_randomize' => ['sometimes', 'boolean'],
        ]);
    }

    /** @return array<string, mixed> */
    private function validateSession(Request $request, bool $creating): array
    {
        $req = $creating ? 'required' : 'sometimes';

        return $request->validate([
            'room_id' => ['nullable', 'string', Rule::exists('pmb_exam_rooms', 'id')->where('university_id', app(TenantContext::class)->universityId())],
            'code' => [$req, 'string', 'max:30'],
            'starts_at' => [$req, 'date'],
            'ends_at' => [$req, 'date'],
            'checkin_opens_at' => ['nullable', 'date'],
            'capacity' => [$req, 'integer', 'min:1', 'max:5000'],
            'online_url' => ['nullable', 'url', 'max:500'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);
    }

    /** @return array<string, mixed> */
    private function validateQuestion(Request $request, bool $creating): array
    {
        $req = $creating ? 'required' : 'sometimes';

        return $request->validate([
            'question' => [$req, 'string', 'max:10000'],
            'options' => [$req, 'array', 'min:2', 'max:8'],
            'options.*.key' => ['required', 'string', 'max:5'],
            'options.*.text' => ['required', 'string', 'max:2000'],
            'correct_option' => [$req, 'string', 'max:5'],
            'points' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);
    }
}
