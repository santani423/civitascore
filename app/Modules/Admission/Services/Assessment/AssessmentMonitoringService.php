<?php

namespace Modules\Admission\Services\Assessment;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Modules\Admission\Enums\AssessmentPurpose;
use Modules\Admission\Enums\AssessmentReviewNoteKind;
use Modules\Admission\Enums\AssessmentReviewStatus;
use Modules\Admission\Enums\AssessmentSessionStatus;
use Modules\Admission\Enums\AssessmentVersionStatus;
use Modules\Admission\Exceptions\DomainRuleException;
use Modules\Admission\Models\Application;
use Modules\Admission\Models\AssessmentDimension;
use Modules\Admission\Models\AssessmentItem;
use Modules\Admission\Models\AssessmentResponse;
use Modules\Admission\Models\AssessmentReviewNote;
use Modules\Admission\Models\AssessmentScore;
use Modules\Admission\Models\AssessmentSession;
use Modules\Admission\Models\AssessmentType;
use Modules\Admission\Models\AssessmentVersion;
use Modules\Admission\Support\HistoryRecorder;
use Modules\AuditLog\Enums\AuditAction;
use Modules\AuditLog\Models\AuditLog;

/**
 * Monitoring peserta, dashboard, review hasil, dan invalidasi sesi (docs/one-atma-talent/04, 06).
 * Nama peserta hanya dikirim bila pemanggil punya `pmb_applications.read`; jawaban mentah hanya
 * bila punya `pmb_sensitive.read`. Keduanya diputuskan di sini, bukan di frontend.
 */
final class AssessmentMonitoringService
{
    /** Distribusi hasil hanya ditampilkan bila jumlah sesi terbit minimal ini (perlindungan kelompok kecil). */
    public const DISTRIBUTION_MIN_PUBLISHED = 10;

    /** Label dimensi Holland untuk distribusi agregat. Bukan interpretasi psikometrik. */
    public const HOLLAND_LABELS = [
        'R' => 'Realistic', 'I' => 'Investigative', 'A' => 'Artistic',
        'S' => 'Social', 'E' => 'Enterprising', 'C' => 'Conventional',
    ];

    public function __construct(
        private readonly AssessmentSessionService $sessions,
        private readonly HistoryRecorder $history,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<AssessmentSession>
     */
    public function sessionsQuery(array $filters, bool $canSeeName): Builder
    {
        return $this->applyFilters(AssessmentSession::query(), $filters, $canSeeName)
            ->with([
                'application:id,registration_number,applicant_account_id,period_id,submitted_at',
                'application.account:id,name',
                'application.period:id,name,code',
                'application.choices' => fn ($q) => $q->where('priority', 1)->with('studyProgram:id,name,code'),
                'version:id,title,version_no,assessment_type_id,status',
                'version.type:id,name,code',
            ])
            ->orderByDesc('started_at');
    }

    /**
     * @return array<string, mixed>
     */
    public function sessionRow(AssessmentSession $session, bool $canSeeName): array
    {
        $application = $session->application;
        $program = $application->choices->first()?->studyProgram;

        return [
            'id' => $session->getKey(),
            'registration_number' => $application->registration_number,
            'applicant_name' => $canSeeName ? $application->account->name : null,
            'study_program' => $program === null ? null : ['id' => $program->getKey(), 'name' => $program->name, 'code' => $program->code],
            'period' => ['id' => $application->period->getKey(), 'name' => $application->period->name],
            'assessment' => ['id' => $session->version->type->getKey(), 'name' => $session->version->type->name],
            'version' => ['id' => $session->version->getKey(), 'title' => $session->version->title, 'version_no' => $session->version->version_no, 'status' => $session->version->status->value],
            'mode' => $session->mode,
            'started_at' => $session->started_at->toIso8601String(),
            'submitted_at' => $session->submitted_at?->toIso8601String(),
            'status' => $session->status->value,
            'review_status' => $session->review_status->value,
            'result_status' => $session->status === AssessmentSessionStatus::Published ? 'published' : ($session->scored_at !== null ? 'scored' : 'pending'),
            'holland_code' => $session->status === AssessmentSessionStatus::Published ? $session->holland_code : null,
        ];
    }

    /**
     * Dashboard One Atma Talent. Hitungan dihitung dari data aktual dengan filter yang sama
     * dengan daftar peserta. Distribusi hasil hanya untuk pemanggil yang berwenang.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function dashboard(array $filters, bool $includeResults): array
    {
        $scope = fn (): Builder => $this->applyFilters(AssessmentSession::query(), $filters);

        $statusCounts = $scope()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $statuses = collect(AssessmentSessionStatus::cases())->mapWithKeys(fn (AssessmentSessionStatus $s) => [$s->value => (int) ($statusCounts[$s->value] ?? 0)]);

        $since = CarbonImmutable::now()->subDays(29)->startOfDay();
        $submittedByDay = $scope()->whereNotNull('submitted_at')->where('submitted_at', '>=', $since)
            ->selectRaw('date(submitted_at) as day, count(*) as total')->groupBy('day')->pluck('total', 'day');
        $startedByDay = $scope()->where('started_at', '>=', $since)
            ->selectRaw('date(started_at) as day, count(*) as total')->groupBy('day')->pluck('total', 'day');

        $trend = [];

        for ($day = $since; $day->lte(CarbonImmutable::now()); $day = $day->addDay()) {
            $key = $day->toDateString();
            $trend[] = ['date' => $key, 'started' => (int) ($startedByDay[$key] ?? 0), 'submitted' => (int) ($submittedByDay[$key] ?? 0)];
        }

        // Subquery, bukan JOIN: scope tenant menambahkan `university_id` tanpa prefiks tabel dan akan ambigu di JOIN.
        $byPeriod = DB::table('pmb_applications as a')
            ->join('pmb_periods as p', 'p.id', '=', 'a.period_id')
            ->whereIn('a.id', $scope()->select('application_id'))
            ->selectRaw('p.id as period_id, p.name as period_name, count(a.id) as total')
            ->groupBy('p.id', 'p.name')
            ->orderBy('p.name')
            ->get()
            ->map(fn ($row) => ['period_id' => $row->period_id, 'period_name' => $row->period_name, 'participants' => (int) $row->total])
            ->values();

        $result = [
            'totals' => [
                'assessments' => AssessmentType::query()->when(! empty($filters['type_id']), fn ($q) => $q->whereKey($filters['type_id']))->count(),
                'published_versions' => AssessmentVersion::query()->where('status', AssessmentVersionStatus::Published->value)->count(),
                'participants' => (int) $scope()->distinct('application_id')->count('application_id'),
                'not_started' => $this->notStartedCount($filters),
                'in_progress' => $statuses[AssessmentSessionStatus::InProgress->value],
                'submitted' => $statuses[AssessmentSessionStatus::Submitted->value],
                'pending_scoring' => (int) $scope()->whereIn('status', [AssessmentSessionStatus::Submitted->value, AssessmentSessionStatus::Expired->value])->whereNull('scored_at')->count(),
                'needs_review' => (int) $scope()->where('review_status', AssessmentReviewStatus::Flagged->value)->count(),
                'published' => $statuses[AssessmentSessionStatus::Published->value],
                'expired' => $statuses[AssessmentSessionStatus::Expired->value],
                'invalidated' => $statuses[AssessmentSessionStatus::Invalidated->value],
            ],
            'status_distribution' => $statuses,
            'completion_trend' => $trend,
            'participants_by_period' => $byPeriod,
            'result_distribution' => null,
            'filters' => $filters,
        ];

        if ($includeResults) {
            $published = (int) $scope()->where('status', AssessmentSessionStatus::Published->value)->count();
            $letters = $scope()->where('status', AssessmentSessionStatus::Published->value)->whereNotNull('holland_code')
                ->selectRaw('substr(holland_code, 1, 1) as letter, count(*) as total')->groupBy('letter')->pluck('total', 'letter');

            $result['result_distribution'] = [
                'published_total' => $published,
                'suppressed' => $published < self::DISTRIBUTION_MIN_PUBLISHED,
                'min_published' => self::DISTRIBUTION_MIN_PUBLISHED,
                'items' => $published < self::DISTRIBUTION_MIN_PUBLISHED ? [] : collect(self::HOLLAND_LABELS)->map(fn (string $name, string $code) => [
                    'code' => $code, 'name' => $name, 'total' => (int) ($letters[$code] ?? 0),
                ])->values()->all(),
            ];
        }

        return $result;
    }

    /**
     * Detail sesi untuk admin. Jawaban mentah hanya bila `$canSeeResponses`; riwayat event
     * dan catatan review selalu tampil karena merupakan jejak audit.
     *
     * @return array<string, mixed>
     */
    public function detail(AssessmentSession $session, bool $canSeeName, bool $canSeeResponses): array
    {
        $session->load([
            'application.account:id,name,email',
            'application.period:id,name,code',
            'application.choices' => fn ($q) => $q->where('priority', 1)->with('studyProgram:id,name,code'),
            'version.type:id,name,code',
            'scores',
            'events',
            'reviewNotes.author:id,name',
        ]);

        $names = $this->actorNames($session->events->pluck('actor')->merge([$session->invalidated_by])->filter()->all());

        return [
            ...$this->sessionRow($session, $canSeeName),
            'version_id' => $session->version_id,
            'deadline_at' => $session->deadline_at?->toIso8601String(),
            'published_at' => $session->published_at?->toIso8601String(),
            'scored_at' => $session->scored_at?->toIso8601String(),
            'scoring_engine' => $session->scoring_engine,
            'dominant_dimensions' => $session->dominant_dimensions,
            'invalidation' => $session->invalidated_at === null ? null : [
                'at' => $session->invalidated_at->toIso8601String(),
                'by' => $session->invalidated_by ? ($names[$session->invalidated_by] ?? null) : null,
                'reason' => $session->invalidation_reason,
            ],
            'scores' => $session->scores->map(fn (AssessmentScore $s) => $s->only(['dimension_code', 'item_count', 'raw_score', 'mean_score', 'percent_score', 'rank']))->values(),
            'events' => $session->events->sortBy('created_at')->values()->map(fn ($e) => [
                'type' => $e->event_type,
                'actor' => $this->actorLabel($e->actor, $names),
                'payload' => $e->payload,
                'at' => $e->created_at->toIso8601String(),
            ]),
            'review_notes' => $session->reviewNotes->map(fn (AssessmentReviewNote $n) => [
                'kind' => $n->kind->value,
                'body' => $n->body,
                'author' => $n->author?->name,
                'at' => $n->created_at->toIso8601String(),
            ])->values(),
            'responses' => $canSeeResponses ? $this->responses($session) : null,
            'responses_restricted' => ! $canSeeResponses,
        ];
    }

    /**
     * Ruang review hasil: checklist kelengkapan, skor, dan riwayat publikasi. `can_publish`
     * hanya true bila semua item checklist terpenuhi.
     *
     * @return array<string, mixed>
     */
    public function review(AssessmentSession $session): array
    {
        $session->loadMissing(['version.type', 'scores', 'reviewNotes.author:id,name', 'events']);

        $checks = [
            ['key' => 'status', 'label' => 'Sesi sudah dikirim atau kedaluwarsa', 'ok' => in_array($session->status, [AssessmentSessionStatus::Submitted, AssessmentSessionStatus::Expired], true)],
            ['key' => 'scores', 'label' => 'Skor tersedia', 'ok' => $session->scores->isNotEmpty()],
            ['key' => 'review', 'label' => 'Tidak ditandai perlu review', 'ok' => $session->review_status !== AssessmentReviewStatus::Flagged],
            ['key' => 'not_invalidated', 'label' => 'Sesi tidak diinvalidasi', 'ok' => $session->status !== AssessmentSessionStatus::Invalidated],
        ];

        return [
            'session_id' => $session->getKey(),
            'status' => $session->status->value,
            'review_status' => $session->review_status->value,
            'version' => ['id' => $session->version_id, 'title' => $session->version->title, 'version_no' => $session->version->version_no, 'status' => $session->version->status->value],
            'scoring_engine' => $session->scoring_engine,
            'holland_code' => $session->holland_code,
            'dominant_dimensions' => $session->dominant_dimensions,
            'scored_at' => $session->scored_at?->toIso8601String(),
            'scores' => $session->scores->map(fn (AssessmentScore $s) => $s->only(['dimension_code', 'item_count', 'raw_score', 'mean_score', 'percent_score', 'rank']))->values(),
            'checks' => $checks,
            'can_publish' => collect($checks)->every(fn (array $c) => $c['ok']) && $session->status !== AssessmentSessionStatus::Published,
            'review_notes' => $session->reviewNotes->map(fn (AssessmentReviewNote $n) => [
                'kind' => $n->kind->value, 'body' => $n->body, 'author' => $n->author?->name, 'at' => $n->created_at->toIso8601String(),
            ])->values(),
            'publication_history' => $session->events->where('event_type', 'published')->sortBy('created_at')->values()->map(fn ($e) => [
                'at' => $e->created_at->toIso8601String(),
                'payload' => $e->payload,
            ])->values(),
        ];
    }

    public function addNote(AssessmentSession $session, User $actor, string $body): AssessmentReviewNote
    {
        return $this->recordReview($session, $actor, AssessmentReviewNoteKind::Note, $body);
    }

    /** Tandai sesi perlu review. Menahan publikasi sampai review selesai. */
    public function flag(AssessmentSession $session, User $actor, string $reason): AssessmentSession
    {
        $this->assertReviewable($session);

        $session->forceFill(['review_status' => AssessmentReviewStatus::Flagged->value])->save();
        $this->recordReview($session, $actor, AssessmentReviewNoteKind::Flag, $reason);

        return $session->refresh();
    }

    /** Batalkan penandaan tanpa menyelesaikan review. */
    public function unflag(AssessmentSession $session, User $actor, string $note): AssessmentSession
    {
        $this->assertReviewable($session);

        if ($session->review_status !== AssessmentReviewStatus::Flagged) {
            throw new DomainRuleException('Sesi ini tidak sedang ditandai perlu review.', 'NOT_FLAGGED', 409);
        }

        $session->forceFill(['review_status' => AssessmentReviewStatus::None->value])->save();
        $this->recordReview($session, $actor, AssessmentReviewNoteKind::Unflag, $note);

        return $session->refresh();
    }

    /** Tandai review selesai. Sesi yang ditandai ikut dibebaskan dari penahanan publikasi. */
    public function completeReview(AssessmentSession $session, User $actor, ?string $note): AssessmentSession
    {
        $this->assertReviewable($session);

        $session->forceFill(['review_status' => AssessmentReviewStatus::Reviewed->value])->save();
        $this->recordReview($session, $actor, AssessmentReviewNoteKind::ReviewCompleted, $note);

        return $session->refresh();
    }

    public function invalidate(AssessmentSession $session, User $actor, string $reason): AssessmentSession
    {
        return $this->sessions->invalidate($session, $actor, $reason);
    }

    /**
     * Riwayat audit One Atma Talent (jenis, versi, dimensi, butir, sesi).
     *
     * @param  array<string, mixed>  $filters
     * @return Builder<AuditLog>
     */
    public function auditQuery(array $filters): Builder
    {
        $types = [
            (new AssessmentType)->getMorphClass(), (new AssessmentVersion)->getMorphClass(),
            (new AssessmentDimension)->getMorphClass(), (new AssessmentItem)->getMorphClass(),
            (new AssessmentSession)->getMorphClass(),
        ];

        return AuditLog::query()
            ->with('user:id,name')
            ->whereIn('auditable_type', $types)
            ->when(! empty($filters['auditable_type']), fn (Builder $q) => $q->where('auditable_type', $filters['auditable_type']))
            ->when(! empty($filters['auditable_id']), fn (Builder $q) => $q->where('auditable_id', $filters['auditable_id']))
            ->when(! empty($filters['action']), fn (Builder $q) => $q->where('action', $filters['action']))
            ->when(! empty($filters['from']), fn (Builder $q) => $q->where('created_at', '>=', $filters['from']))
            ->when(! empty($filters['to']), fn (Builder $q) => $q->where('created_at', '<=', $filters['to']))
            ->orderByDesc('created_at');
    }

    /**
     * @param  Builder<AssessmentSession>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<AssessmentSession>
     */
    private function applyFilters(Builder $query, array $filters, bool $searchName = false): Builder
    {
        return $query
            ->when(! empty($filters['status']), fn (Builder $q) => $q->where('status', $filters['status']))
            ->when(! empty($filters['review_status']), fn (Builder $q) => $q->where('review_status', $filters['review_status']))
            ->when(! empty($filters['mode']), fn (Builder $q) => $q->where('mode', $filters['mode']))
            ->when(! empty($filters['version_id']), fn (Builder $q) => $q->where('version_id', $filters['version_id']))
            ->when(! empty($filters['type_id']), fn (Builder $q) => $q->whereHas('version', fn (Builder $v) => $v->where('assessment_type_id', $filters['type_id'])))
            ->when(! empty($filters['period_id']), fn (Builder $q) => $q->whereHas('application', fn (Builder $a) => $a->where('period_id', $filters['period_id'])))
            ->when(! empty($filters['study_program_id']), fn (Builder $q) => $q->whereHas('application.choices', fn (Builder $c) => $c->where('priority', 1)->where('study_program_id', $filters['study_program_id'])))
            ->when(! empty($filters['search']), function (Builder $q) use ($filters, $searchName): void {
                $term = '%'.addcslashes((string) $filters['search'], '%_\\').'%';
                $q->whereHas('application', function (Builder $a) use ($term, $searchName): void {
                    $a->where('registration_number', 'like', $term)
                        ->when($searchName, fn (Builder $n) => $n->orWhereHas('account', fn (Builder $acc) => $acc->where('name', 'like', $term)));
                });
            });
    }

    /**
     * Pendaftaran terkirim yang belum punya sesi untuk versi aktif dalam cakupan filter.
     *
     * @param  array<string, mixed>  $filters
     */
    private function notStartedCount(array $filters): int
    {
        $versions = AssessmentVersion::query()
            ->where('status', AssessmentVersionStatus::Published->value)
            ->where('purpose', AssessmentPurpose::Exploratory->value)
            ->when(! empty($filters['type_id']), fn (Builder $q) => $q->where('assessment_type_id', $filters['type_id']))
            ->when(! empty($filters['version_id']), fn (Builder $q) => $q->whereKey($filters['version_id']))
            ->get(['id', 'period_id']);

        $total = 0;

        foreach ($versions as $version) {
            $total += Application::query()
                ->whereNotNull('submitted_at')
                ->whereNull('withdrawn_at')
                ->when($version->period_id !== null, fn (Builder $q) => $q->where('period_id', $version->period_id))
                ->when(! empty($filters['period_id']), fn (Builder $q) => $q->where('period_id', $filters['period_id']))
                ->when(! empty($filters['study_program_id']), fn (Builder $q) => $q->whereHas('choices', fn (Builder $c) => $c->where('priority', 1)->where('study_program_id', $filters['study_program_id'])))
                ->whereNotIn('id', AssessmentSession::query()->where('version_id', $version->getKey())->select('application_id'))
                ->count();
        }

        return $total;
    }

    private function assertReviewable(AssessmentSession $session): void
    {
        if ($session->status === AssessmentSessionStatus::Invalidated) {
            throw new DomainRuleException('Sesi yang diinvalidasi tidak dapat direview ulang.', 'SESSION_INVALIDATED', 409);
        }

        if ($session->status === AssessmentSessionStatus::Published) {
            throw new DomainRuleException('Hasil yang sudah dipublikasikan tidak dapat direview ulang.', 'ALREADY_PUBLISHED', 409);
        }
    }

    private function recordReview(AssessmentSession $session, User $actor, AssessmentReviewNoteKind $kind, ?string $body): AssessmentReviewNote
    {
        $note = AssessmentReviewNote::query()->create([
            'university_id' => $session->university_id,
            'session_id' => $session->getKey(),
            'author_id' => $actor->getKey(),
            'kind' => $kind->value,
            'body' => $body,
            'created_at' => now(),
        ]);

        $this->history->action($session, AuditAction::Updated, ['event' => 'review_'.$kind->value], $body);

        return $note;
    }

    /** @return array<int, array{dimension_code: string, question: string, value: int, reversed: bool}> */
    private function responses(AssessmentSession $session): array
    {
        $order = array_flip($session->item_order);

        return AssessmentResponse::query()->with('item.dimension:id,code')->where('session_id', $session->getKey())->get()
            ->sortBy(fn (AssessmentResponse $r) => $order[$r->item_id] ?? PHP_INT_MAX)
            ->map(fn (AssessmentResponse $r) => [
                'item_id' => $r->item_id,
                'question' => $r->item->stem,
                'dimension_code' => $r->item->dimension->code,
                'value' => $r->value,
                'reversed' => $r->item->is_reversed,
            ])->values()->all();
    }

    /**
     * Nama pengguna untuk label aktor (`user:{id}`) dan pembatal invalidasi (id mentah).
     *
     * @param  array<int, string|null>  $actors
     * @return array<string, string>
     */
    private function actorNames(array $actors): array
    {
        $ids = collect($actors)
            ->filter()
            ->map(fn (string $a) => str_starts_with($a, 'user:') ? substr($a, 5) : $a)
            ->reject(fn (string $a) => in_array($a, ['system'], true) || str_starts_with($a, 'applicant:'))
            ->unique()
            ->values();

        return User::query()->whereIn('id', $ids)->pluck('name', 'id')->all();
    }

    /** @param  array<string, string>  $names */
    private function actorLabel(?string $actor, array $names): string
    {
        return match (true) {
            $actor === null => 'Sistem',
            $actor === 'system' => 'Sistem',
            str_starts_with($actor, 'applicant:') => 'Peserta',
            str_starts_with($actor, 'user:') => $names[substr($actor, 5)] ?? 'Staf',
            default => 'Sistem',
        };
    }
}
