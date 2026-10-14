<?php

namespace Modules\Admission\Controllers\Admin;

use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Admission\Enums\Recommendation;
use Modules\Admission\Models\Application;
use Modules\Admission\Models\ExamSchedule;
use Modules\Admission\Models\ScoreImport;
use Modules\Admission\Models\SelectionComponent;
use Modules\Admission\Models\SelectionRun;
use Modules\Admission\Models\SelectionRunResult;
use Modules\Admission\Models\SelectionScore;
use Modules\Admission\Models\WavePath;
use Modules\Admission\Services\Selection\ScoreService;
use Modules\Admission\Services\Selection\SelectionRunService;
use Modules\Admission\Support\PmbFileStorage;
use Modules\Admission\Support\PmbPermission;
use Modules\Admission\Support\ScopeResolver;

/** Nilai, run seleksi, peninjauan, override, pengesahan (FR-SEL, ADM-47..52). */
class SelectionController extends AdminController
{
    public function __construct(private readonly SelectionRunService $runs, private readonly ScoreService $scores) {}

    // ------------------------------------------------------------------ Nilai

    public function scores(Request $request, WavePath $wavePath): JsonResponse
    {
        $wavePath->load('selectionScheme.components');
        $components = $wavePath->selectionScheme?->components->sortBy('sort_order')->values() ?? collect();
        $query = Application::query()->with(['account:id,name', 'scores'])->where('wave_path_id', $wavePath->getKey())
            ->where('verification_status', 'verified')->whereNotIn('form_status', ['withdrawn', 'void'])
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($w) => $w->where('registration_number', 'like', '%'.$request->string('search').'%')
                ->orWhereHas('account', fn ($a) => $a->where('name', 'like', '%'.$request->string('search').'%'))))
            ->orderBy('registration_number');
        app(ScopeResolver::class)->constrainApplications($query, $this->user($request), PmbPermission::SELECTION_MANAGE);

        $response = $this->paginate($query, $request, fn (Application $a) => [
            'application_id' => $a->getKey(),
            'registration_number' => $a->registration_number,
            'name' => $a->account?->name,
            'selection_status' => $a->selection_status->value,
            'scores' => $components->mapWithKeys(fn ($c) => [$c->code => $a->scores->where('selection_component_id', $c->getKey())->values()->map(fn (SelectionScore $s) => [
                'id' => $s->getKey(), 'raw_score' => $s->raw_score, 'passed' => $s->passed, 'source' => $s->source?->value ?? $s->source,
                'examiner_user_id' => $s->examiner_user_id, 'locked' => $s->locked_at !== null,
            ])]),
        ], 50);
        $payload = $response->getData(true);
        $payload['meta']['components'] = $components->map->only(['id', 'code', 'name', 'source', 'score_type', 'raw_min', 'raw_max', 'weight', 'is_mandatory'])->values();

        return response()->json($payload);
    }

    public function correctScore(Request $request, SelectionScore $selectionScore): JsonResponse
    {
        $data = $request->validate(['raw_score' => ['nullable', 'numeric'], 'passed' => ['nullable', 'boolean'], 'reason' => ['required', 'string', 'min:5', 'max:500']]);
        $this->assertApplicationScope($request, PmbPermission::SELECTION_MANAGE, $selectionScore->application_id);
        $score = $this->scores->correct($selectionScore, isset($data['raw_score']) ? (float) $data['raw_score'] : null, $data['passed'] ?? null, $data['reason'], $this->user($request));

        return ApiResponse::success($score->only(['id', 'raw_score', 'passed']), 'Nilai dikoreksi.');
    }

    /** Koreksi nilai yang sudah terkunci run → lewat persetujuan (BR-31). */
    public function requestScoreCorrection(Request $request, PmbFileStorage $storage): JsonResponse
    {
        $data = $request->validate([
            'selection_score_id' => ['required', 'string', 'size:26'],
            'raw_score' => ['nullable', 'numeric'],
            'passed' => ['nullable', 'boolean'],
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
            'evidence' => ['nullable', 'file', 'max:5120'],
        ]);
        $score = SelectionScore::query()->findOrFail($data['selection_score_id']);
        $evidenceId = $request->hasFile('evidence') ? $storage->store($request->file('evidence'), ['image/jpeg', 'image/png', 'application/pdf'], 5120, ['evidence'], uploadedByUserId: (string) $this->user($request)->getKey())->getKey() : null;
        $change = $this->scores->requestLockedCorrection($score, isset($data['raw_score']) ? (float) $data['raw_score'] : null, $data['passed'] ?? null, $data['reason'], $evidenceId, $this->user($request));

        return ApiResponse::success(['change_request_id' => $change->getKey(), 'status' => $change->status->value], 'Koreksi nilai diajukan untuk persetujuan.', status: 201);
    }

    public function validateImport(Request $request): JsonResponse
    {
        $data = $request->validate([
            'exam_schedule_id' => ['required', 'string', 'size:26'],
            'selection_component_id' => ['required', 'string', 'size:26'],
            'file' => ['required', 'file', 'max:10240'],
        ]);
        $import = $this->scores->validateImport(ExamSchedule::query()->findOrFail($data['exam_schedule_id']), SelectionComponent::query()->findOrFail($data['selection_component_id']), $request->file('file'), $this->user($request));

        return ApiResponse::success([
            'id' => $import->getKey(), 'status' => $import->status,
            'valid_count' => count((array) $import->valid_rows), 'errors' => $import->errors,
            'preview' => array_slice((array) $import->valid_rows, 0, 20),
        ], 'Berkas divalidasi.', status: 201);
    }

    public function commitImport(Request $request, ScoreImport $scoreImport): JsonResponse
    {
        return ApiResponse::success(['saved' => $this->scores->commitImport($scoreImport, $this->user($request))], 'Nilai diimpor.');
    }

    // ------------------------------------------------------------------ Run seleksi

    public function runs(Request $request): JsonResponse
    {
        $query = SelectionRun::query()->with(['wavePath.wave:id,code,name', 'wavePath.admissionPath:id,code,name', 'scheme:id,code,name,version', 'creator:id,name'])
            ->when($request->filled('wave_path_id'), fn ($q) => $q->where('wave_path_id', $request->string('wave_path_id')))
            ->when($request->filled('status'), fn ($q) => $q->whereIn('status', explode(',', $request->string('status'))))
            ->orderByDesc('created_at');

        return $this->paginate($query, $request, fn (SelectionRun $r) => [
            'id' => $r->getKey(), 'status' => $r->status->value, 'run_type' => $r->run_type,
            'wave' => $r->wavePath?->wave?->name, 'path' => $r->wavePath?->admissionPath?->name, 'wave_path_id' => $r->wave_path_id,
            'scheme' => $r->scheme ? $r->scheme->code.' v'.$r->scheme->version : null,
            'summary' => $r->summary, 'has_unresolved_ties' => $r->has_unresolved_ties, 'result_hash' => $r->result_hash,
            'created_by' => $r->creator?->name, 'computed_at' => $r->computed_at?->toIso8601String(), 'approved_at' => $r->approved_at?->toIso8601String(),
            'failure_reason' => $r->failure_reason, 'created_at' => $r->created_at?->toIso8601String(),
        ]);
    }

    public function storeRun(Request $request): JsonResponse
    {
        $data = $request->validate(['wave_path_id' => ['required', 'string', 'size:26']]);
        $run = $this->runs->create(WavePath::query()->with('selectionScheme')->findOrFail($data['wave_path_id']), $this->user($request));

        return ApiResponse::success(['id' => $run->getKey(), 'status' => $run->status->value], 'Run seleksi dibuat; perhitungan berjalan.', status: 201);
    }

    public function run(SelectionRun $selectionRun): JsonResponse
    {
        $selectionRun->load(['wavePath.wave', 'wavePath.admissionPath', 'scheme.components', 'creator:id,name', 'submitter:id,name', 'batches']);

        return ApiResponse::success([
            ...$selectionRun->only(['id', 'run_type', 'wave_path_id', 'summary', 'quota_snapshot', 'result_hash', 'has_unresolved_ties', 'review_note', 'failure_reason', 'superseded_by_id', 'correction_request_id']),
            'status' => $selectionRun->status->value,
            'wave' => $selectionRun->wavePath?->wave?->only(['id', 'code', 'name', 'announcement_at']),
            'path' => $selectionRun->wavePath?->admissionPath?->only(['id', 'code', 'name']),
            'scheme' => $selectionRun->scheme?->only(['id', 'code', 'name', 'version', 'passing_score', 'allocation_method']),
            'components' => $selectionRun->scheme?->components->sortBy('sort_order')->values()->map->only(['id', 'code', 'name', 'weight', 'score_type']),
            'created_by' => $selectionRun->creator?->name, 'submitted_by' => $selectionRun->submitter?->name,
            'computed_at' => $selectionRun->computed_at?->toIso8601String(), 'approved_at' => $selectionRun->approved_at?->toIso8601String(),
            'current_hash' => in_array($selectionRun->status->value, ['computed', 'under_review', 'pending_approval'], true) ? $this->runs->currentHash($selectionRun) : null,
            'approval' => $this->approvalSummary($selectionRun->approval_request_id),
            'batches' => $selectionRun->batches->map->only(['id', 'scheduled_publish_at', 'status', 'published_at', 'decisions_count', 'failure_reason']),
        ]);
    }

    public function results(Request $request, SelectionRun $selectionRun): JsonResponse
    {
        $query = SelectionRunResult::query()->where('selection_run_id', $selectionRun->getKey())
            ->with(['application:id,registration_number,applicant_account_id', 'application.account:id,name', 'assignedQuota.studyProgram:id,name', 'assignedQuota.studySystem:id,name'])
            ->when($request->filled('recommendation'), fn ($q) => $q->where(fn ($w) => $w->where('override_recommendation', $request->string('recommendation'))
                ->orWhere(fn ($x) => $x->whereNull('override_recommendation')->where('recommendation', $request->string('recommendation')))))
            ->when($request->boolean('ties_only'), fn ($q) => $q->where('tie_unresolved', true))
            ->when($request->filled('search'), fn ($q) => $q->whereHas('application', fn ($a) => $a->where('registration_number', 'like', '%'.$request->string('search').'%')
                ->orWhereHas('account', fn ($ac) => $ac->where('name', 'like', '%'.$request->string('search').'%'))))
            ->orderByRaw('rank_global is null')->orderBy('rank_global');
        app(ScopeResolver::class)->constrainApplications($query, $this->user($request), PmbPermission::RESULTS_READ_UNPUBLISHED, 'application_id');

        return $this->paginate($query, $request, fn (SelectionRunResult $r) => [
            'id' => $r->getKey(), 'application_id' => $r->application_id,
            'registration_number' => $r->application?->registration_number, 'name' => $r->application?->account?->name,
            'eligible' => $r->eligible, 'ineligible_reason' => $r->ineligible_reason,
            'component_scores' => $r->component_scores, 'final_score' => $r->final_score_rounded, 'rank' => $r->rank_global,
            'recommendation' => $r->recommendation?->value ?? $r->recommendation, 'fail_reason' => $r->fail_reason,
            'effective_recommendation' => $r->effectiveRecommendation(),
            'assigned_program' => $r->assignedQuota?->studyProgram?->name, 'assigned_system' => $r->assignedQuota?->studySystem?->name,
            'waitlist_entries' => $r->waitlist_entries, 'tie_break_values' => $r->tie_break_values, 'tie_unresolved' => $r->tie_unresolved,
            'override' => $r->override_recommendation ? ['recommendation' => $r->override_recommendation, 'quota_id' => $r->override_quota_id, 'reason' => $r->override_reason, 'by' => $r->override_by] : null,
        ], 50);
    }

    public function review(Request $request, SelectionRun $selectionRun): JsonResponse
    {
        return ApiResponse::success(['status' => $this->runs->startReview($selectionRun, $this->user($request))->status->value], 'Peninjauan dimulai.');
    }

    public function override(Request $request, SelectionRunResult $selectionRunResult): JsonResponse
    {
        $data = $request->validate([
            'recommendation' => ['required', Rule::in(Recommendation::values())],
            'quota_id' => ['required_if:recommendation,pass', 'nullable', 'string', 'size:26'],
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);
        $this->assertApplicationScope($request, PmbPermission::SELECTION_MANAGE, $selectionRunResult->application_id);
        $result = $this->runs->override($selectionRunResult, $data['recommendation'], $data['quota_id'] ?? null, $data['reason'], $this->user($request));

        return ApiResponse::success(['id' => $result->getKey(), 'effective_recommendation' => $result->effectiveRecommendation()], 'Override disimpan.');
    }

    public function resolveTies(Request $request, SelectionRun $selectionRun): JsonResponse
    {
        $data = $request->validate([
            'groups' => ['required', 'array', 'min:1'],
            'groups.*.application_ids_ordered' => ['required', 'array', 'min:2'],
            'groups.*.application_ids_ordered.*' => ['string', 'size:26'],
            'groups.*.reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);
        $run = $this->runs->resolveTies($selectionRun, $data['groups'], $this->user($request));

        return ApiResponse::success(['id' => $run->getKey(), 'status' => $run->status->value], 'Urutan seri ditetapkan; run baru dihitung.', status: 201);
    }

    public function submit(Request $request, SelectionRun $selectionRun): JsonResponse
    {
        $data = $request->validate(['note' => ['nullable', 'string', 'max:2000']]);
        $run = $this->runs->submit($selectionRun, $data['note'] ?? null, $this->user($request));

        return ApiResponse::success(['status' => $run->status->value, 'approval' => $this->approvalSummary($run->approval_request_id)], 'Hasil diajukan untuk pengesahan.');
    }
}
