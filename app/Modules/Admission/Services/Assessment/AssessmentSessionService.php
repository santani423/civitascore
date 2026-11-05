<?php

namespace Modules\Admission\Services\Assessment;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Admission\Enums\AssessmentPurpose;
use Modules\Admission\Enums\AssessmentReviewStatus;
use Modules\Admission\Enums\AssessmentSessionStatus;
use Modules\Admission\Enums\AssessmentVersionStatus;
use Modules\Admission\Enums\ConsentType;
use Modules\Admission\Exceptions\DomainRuleException;
use Modules\Admission\Models\AccountConsent;
use Modules\Admission\Models\ApplicantAccount;
use Modules\Admission\Models\Application;
use Modules\Admission\Models\AssessmentItem;
use Modules\Admission\Models\AssessmentResponse;
use Modules\Admission\Models\AssessmentScore;
use Modules\Admission\Models\AssessmentSession;
use Modules\Admission\Models\AssessmentSessionEvent;
use Modules\Admission\Models\AssessmentVersion;
use Modules\Admission\Services\Account\AccountService;
use Modules\Admission\Services\Assessment\Scoring\RiasecScorer;
use Modules\Admission\Support\HistoryRecorder;
use Modules\Admission\Support\StateTransition;

/**
 * Siklus sesi peserta: daftar asesmen, persetujuan, mulai, simpan jawaban,
 * kirim (atau waktu habis), publikasi, dan hasil. Semua aturan ada di sini,
 * bukan di controller (prompt bagian 6 dan 9).
 */
final class AssessmentSessionService
{
    public const DISCLAIMER = 'Hasil ini bersifat eksploratif. Bukan diagnosis psikologis dan bukan keputusan penerimaan atau pemilihan jurusan.';

    private const PROBLEMS = [
        'APPLICATION_NOT_SUBMITTED' => 'Kirim pendaftaran terlebih dahulu sebelum mengerjakan asesmen.',
        'APPLICATION_WITHDRAWN' => 'Pendaftaran ini telah ditarik.',
        'NOT_OPEN' => 'Asesmen belum dibuka.',
        'CLOSED' => 'Masa pengerjaan asesmen telah berakhir.',
        'CONSENT_REQUIRED' => 'Setujui pemrosesan data asesmen untuk memulai.',
        'NOT_AVAILABLE' => 'Asesmen ini tidak tersedia.',
        'PERIOD_MISMATCH' => 'Asesmen ini tidak berlaku untuk periode pendaftaran Anda.',
    ];

    public function __construct(
        private readonly RiasecScorer $scorer,
        private readonly AccountService $accounts,
        private readonly HistoryRecorder $history,
    ) {}

    /**
     * Asesmen yang tersedia untuk satu pendaftaran, ditambah asesmen yang sudah pernah
     * dikerjakan walaupun versinya sudah diarsipkan (hasilnya tetap dapat dibaca).
     *
     * @return array<int, array<string, mixed>>
     */
    public function catalog(Application $application): array
    {
        $sessions = AssessmentSession::query()->with('version.type')->where('application_id', $application->getKey())->get();
        $answered = AssessmentResponse::query()->whereIn('session_id', $sessions->modelKeys())->selectRaw('session_id, count(*) as total')->groupBy('session_id')->pluck('total', 'session_id');

        $published = AssessmentVersion::query()->with('type')
            ->where('status', AssessmentVersionStatus::Published->value)
            ->where('purpose', AssessmentPurpose::Exploratory->value)
            ->whereHas('type', fn ($q) => $q->where('is_active', true))
            ->where(fn ($q) => $q->whereNull('period_id')->orWhere('period_id', $application->period_id))
            ->orderBy('published_at')
            ->get();

        $consented = $this->hasConsent($application->account);
        $sessionByVersion = $sessions->keyBy('version_id');

        $versions = $published->concat($sessions->pluck('version')->reject(fn ($v) => $published->contains('id', $v->getKey())))->unique(fn ($v) => $v->getKey());

        return $versions->map(function (AssessmentVersion $version) use ($application, $sessionByVersion, $answered, $consented): array {
            $session = $sessionByVersion->get($version->getKey());
            $session = $session ? $this->expireIfDue($session) : null;
            $problem = $session === null ? $this->problem($application, $version, $consented) : null;

            return [
                'id' => $version->getKey(),
                'title' => $version->title,
                'type' => ['code' => $version->type->code, 'name' => $version->type->name],
                'instructions' => $version->instructions,
                'duration_minutes' => $version->duration_minutes,
                'available_from' => $version->available_from?->toIso8601String(),
                'available_until' => $version->available_until?->toIso8601String(),
                'session' => $session === null ? null : [
                    'id' => $session->getKey(),
                    'status' => $session->status->value,
                    'answered_count' => (int) ($answered[$session->getKey()] ?? 0),
                    'total_count' => count($session->item_order),
                    'submitted_at' => $session->submitted_at?->toIso8601String(),
                    'published_at' => $session->published_at?->toIso8601String(),
                ],
                'can_start' => $session === null && $problem === null,
                'blocked_reason' => $problem,
                'blocked_message' => $problem === null ? null : self::PROBLEMS[$problem],
            ];
        })->values()->all();
    }

    /** Mencatat persetujuan pemrosesan data asesmen (berlaku untuk akun). */
    public function consent(ApplicantAccount $account, Application $application, ?string $ip, ?string $userAgent): void
    {
        if ($this->hasConsent($account)) {
            return;
        }

        $this->accounts->recordConsent($account, ConsentType::AssessmentTalent, null, null, $ip, $userAgent, $application->getKey());
    }

    /**
     * Mulai sesi. Pemanggilan ulang mengembalikan sesi yang sama (idempoten).
     * Urutan butir diacak sekali dan dibekukan untuk sesi ini.
     */
    public function start(Application $application, AssessmentVersion $version, string $actor): AssessmentSession
    {
        return DB::transaction(function () use ($application, $version, $actor): AssessmentSession {
            // Kunci pendaftaran agar dua permintaan bersamaan tidak membuat dua sesi.
            Application::query()->whereKey($application->getKey())->lockForUpdate()->firstOrFail();

            $existing = AssessmentSession::query()
                ->where('application_id', $application->getKey())
                ->where('version_id', $version->getKey())
                ->first();

            if ($existing !== null) {
                return $this->expireIfDue($existing);
            }

            $problem = $this->problem($application, $version, $this->hasConsent($application->account));

            if ($problem !== null) {
                throw new DomainRuleException(self::PROBLEMS[$problem], $problem, 422);
            }

            $itemIds = $version->items()->pluck('id')->all();

            if ($itemIds === []) {
                throw new DomainRuleException('Butir asesmen belum tersedia.', 'NO_ITEMS', 422);
            }

            shuffle($itemIds);
            $now = CarbonImmutable::now();

            $session = AssessmentSession::query()->create([
                'university_id' => $application->university_id,
                'application_id' => $application->getKey(),
                'version_id' => $version->getKey(),
                'status' => AssessmentSessionStatus::InProgress->value,
                'item_order' => $itemIds,
                'started_at' => $now,
                'deadline_at' => $version->duration_minutes ? $now->addMinutes($version->duration_minutes) : null,
            ]);

            $this->event($session, 'started', $actor);

            return $session;
        });
    }

    /** @return array<string, mixed> */
    public function payload(AssessmentSession $session): array
    {
        $session = $this->expireIfDue($session);
        $session->loadMissing('version');
        $scale = $this->scale($session->version);

        $items = AssessmentItem::query()->whereIn('id', $session->item_order)->get()->keyBy('id');
        $values = AssessmentResponse::query()->where('session_id', $session->getKey())->pluck('value', 'item_id');

        return [
            'id' => $session->getKey(),
            'status' => $session->status->value,
            'version' => [
                'id' => $session->version->getKey(),
                'title' => $session->version->title,
                'instructions' => $session->version->instructions,
                'duration_minutes' => $session->version->duration_minutes,
            ],
            'scale' => $scale,
            'started_at' => $session->started_at->toIso8601String(),
            'deadline_at' => $session->deadline_at?->toIso8601String(),
            'submitted_at' => $session->submitted_at?->toIso8601String(),
            'server_time' => now()->toIso8601String(),
            'total_count' => count($session->item_order),
            'answered_count' => $values->count(),
            // Dimensi dan arah butir tidak dikirim: keduanya hanya dipakai untuk penilaian.
            'items' => collect($session->item_order)
                ->filter(fn (string $id): bool => $items->has($id))
                ->map(fn (string $id): array => ['id' => $id, 'stem' => $items[$id]->stem, 'value' => $values[$id] ?? null])
                ->values()
                ->all(),
        ];
    }

    /**
     * Simpan jawaban (upsert per butir). Jawaban yang sama dikirim ulang aman, sehingga
     * klien yang terputus dapat mengirim ulang tanpa risiko.
     *
     * @param  list<array{item_id: string, value: int}>  $responses
     * @return array{saved: int, answered_count: int, total_count: int}
     */
    public function saveResponses(AssessmentSession $session, array $responses): array
    {
        $session = $this->expireIfDue($session);
        $this->assertOpen($session);

        return DB::transaction(function () use ($session, $responses): array {
            $session = AssessmentSession::query()->whereKey($session->getKey())->lockForUpdate()->firstOrFail();
            $this->assertOpen($session);

            $scale = $this->scale($session->version);
            $allowed = array_flip($session->item_order);
            $stamp = now()->toDateTimeString();
            $rows = [];

            foreach ($responses as $response) {
                $itemId = (string) $response['item_id'];
                $value = (int) $response['value'];

                if (! array_key_exists($itemId, $allowed)) {
                    throw ValidationException::withMessages(['responses' => 'Ada butir yang tidak termasuk dalam asesmen ini.']);
                }

                if ($value < $scale['min'] || $value > $scale['max']) {
                    throw ValidationException::withMessages(['responses' => "Nilai jawaban harus antara {$scale['min']} dan {$scale['max']}."]);
                }

                $rows[] = [
                    'id' => (string) Str::ulid(),
                    'university_id' => $session->university_id,
                    'session_id' => $session->getKey(),
                    'item_id' => $itemId,
                    'value' => $value,
                    'answered_at' => $stamp,
                    'created_at' => $stamp,
                    'updated_at' => $stamp,
                ];
            }

            if ($rows !== []) {
                AssessmentResponse::query()->upsert($rows, ['session_id', 'item_id'], ['value', 'answered_at', 'updated_at']);
            }

            return [
                'saved' => count($rows),
                'answered_count' => AssessmentResponse::query()->where('session_id', $session->getKey())->count(),
                'total_count' => count($session->item_order),
            ];
        });
    }

    /**
     * Kirim sesi dan hitung skor. Bila waktu sudah habis, sesi ditutup otomatis
     * dan dikembalikan apa adanya (tidak dianggap galat bagi peserta).
     */
    public function submit(AssessmentSession $session, string $actor): AssessmentSession
    {
        $session = $this->expireIfDue($session);

        return $this->close($session, AssessmentSessionStatus::Submitted, $actor);
    }

    /** Menutup sesi yang sudah melewati batas waktu. Dipanggil saat sesi diakses dan dapat dijadwalkan. */
    public function expireDue(): int
    {
        $count = 0;

        AssessmentSession::query()
            ->where('status', AssessmentSessionStatus::InProgress->value)
            ->whereNotNull('deadline_at')
            ->where('deadline_at', '<=', now())
            ->get()
            ->each(function (AssessmentSession $session) use (&$count): void {
                if ($this->expireIfDue($session)->status !== AssessmentSessionStatus::InProgress) {
                    $count++;
                }
            });

        return $count;
    }

    public function publish(AssessmentSession $session, User $actor): AssessmentSession
    {
        return DB::transaction(function () use ($session, $actor): AssessmentSession {
            $session = AssessmentSession::query()->whereKey($session->getKey())->lockForUpdate()->firstOrFail();
            StateTransition::assert($session->status, AssessmentSessionStatus::Published, 'assessment_session');

            if (! AssessmentScore::query()->where('session_id', $session->getKey())->exists()) {
                throw new DomainRuleException('Sesi ini belum memiliki skor untuk dipublikasikan.', 'NO_SCORES', 422);
            }

            if ($session->review_status === AssessmentReviewStatus::Flagged) {
                throw new DomainRuleException('Sesi masih ditandai perlu review. Selesaikan review sebelum dipublikasikan.', 'REVIEW_REQUIRED', 409);
            }

            $old = $session->status->value;
            $session->forceFill([
                'status' => AssessmentSessionStatus::Published->value,
                'published_at' => now(),
                'published_by' => $actor->getKey(),
            ])->save();

            $this->history->change($session, ['status' => $old], ['status' => AssessmentSessionStatus::Published->value]);
            $this->event($session, 'published', 'user:'.$actor->getKey(), [
                'version_id' => $session->version_id,
                'scoring_engine' => $session->scoring_engine,
                'holland_code' => $session->holland_code,
            ]);

            return $session->refresh();
        });
    }

    /**
     * Invalidasi sesi oleh admin dengan alasan. Respons tetap disimpan untuk audit, tetapi
     * sesi tidak lagi dihitung dan hasilnya tidak pernah tampil. Sesi yang sudah terbit tidak dapat diinvalidasi.
     */
    public function invalidate(AssessmentSession $session, User $actor, string $reason): AssessmentSession
    {
        return DB::transaction(function () use ($session, $actor, $reason): AssessmentSession {
            $session = AssessmentSession::query()->whereKey($session->getKey())->lockForUpdate()->firstOrFail();
            StateTransition::assert($session->status, AssessmentSessionStatus::Invalidated, 'assessment_session');

            $old = $session->status->value;
            $session->forceFill([
                'status' => AssessmentSessionStatus::Invalidated->value,
                'invalidated_at' => now(),
                'invalidated_by' => $actor->getKey(),
                'invalidation_reason' => $reason,
            ])->save();

            $this->history->change($session, ['status' => $old], ['status' => AssessmentSessionStatus::Invalidated->value], $reason);
            $this->event($session, 'invalidated', 'user:'.$actor->getKey(), ['reason' => $reason]);

            return $session->refresh();
        });
    }

    /** @return array<string, mixed> */
    public function result(AssessmentSession $session): array
    {
        if ($session->status !== AssessmentSessionStatus::Published) {
            throw new DomainRuleException('Hasil belum dipublikasikan.', 'RESULT_NOT_PUBLISHED', 409);
        }

        $session->loadMissing(['version.type', 'scores']);
        $dimensions = $session->version->dimensions()->get()->keyBy('code');

        return [
            'session_id' => $session->getKey(),
            'assessment' => ['title' => $session->version->title, 'type' => $session->version->type->name],
            'published_at' => $session->published_at?->toIso8601String(),
            'holland_code' => $session->holland_code,
            'dominant' => collect($session->dominant_dimensions ?? [])
                ->map(fn (string $code): array => ['code' => $code, 'name' => $dimensions[$code]->name])
                ->values()
                ->all(),
            'dimensions' => $session->scores->map(fn (AssessmentScore $score): array => [
                'code' => $score->dimension_code,
                'name' => $dimensions[$score->dimension_code]->name,
                'description' => $dimensions[$score->dimension_code]->description,
                'interpretation' => $dimensions[$score->dimension_code]->interpretation,
                'item_count' => $score->item_count,
                'raw_score' => $score->raw_score,
                'mean_score' => $score->mean_score,
                'percent_score' => $score->percent_score,
                'rank' => $score->rank,
            ])->values()->all(),
            'disclaimer' => self::DISCLAIMER,
        ];
    }

    /** Menutup sesi `in_progress` yang batas waktunya sudah lewat. Tanpa efek bila belum lewat. */
    private function expireIfDue(AssessmentSession $session): AssessmentSession
    {
        if ($session->status === AssessmentSessionStatus::InProgress && $session->deadline_at !== null && now()->gte($session->deadline_at)) {
            return $this->close($session, AssessmentSessionStatus::Expired, 'system');
        }

        return $session;
    }

    /**
     * Penutupan sesi: skor dihitung di transaksi yang sama. Untuk `expired` dengan jawaban
     * belum lengkap, sesi ditutup tanpa skor agar hasil tidak dibuat dari data parsial.
     */
    private function close(AssessmentSession $session, AssessmentSessionStatus $target, string $actor): AssessmentSession
    {
        return DB::transaction(function () use ($session, $target, $actor): AssessmentSession {
            $session = AssessmentSession::query()->whereKey($session->getKey())->lockForUpdate()->firstOrFail();

            if ($session->status !== AssessmentSessionStatus::InProgress) {
                return $session;
            }

            StateTransition::assert($session->status, $target, 'assessment_session');
            $session->loadMissing('version');
            $scale = $this->scale($session->version);
            $total = count($session->item_order);
            $responses = AssessmentResponse::query()->with('item.dimension')->where('session_id', $session->getKey())->get();

            if ($responses->count() < $total) {
                if ($target === AssessmentSessionStatus::Submitted) {
                    throw new DomainRuleException('Masih ada '.($total - $responses->count()).' butir yang belum dijawab.', 'UNANSWERED_ITEMS', 422);
                }

                $session->forceFill(['status' => $target->value, 'submitted_at' => now()])->save();
                $this->event($session, 'expired', $actor, ['answered' => $responses->count(), 'total' => $total, 'scored' => false]);

                return $session->refresh();
            }

            /** @var list<array{dimension: string, reversed: bool, value: int}> $answers */
            $answers = $responses->map(fn (AssessmentResponse $r): array => [
                'dimension' => (string) $r->item->dimension->code,
                'reversed' => (bool) $r->item->is_reversed,
                'value' => (int) $r->value,
            ])->values()->all();

            $result = $this->scorer->score(
                $answers,
                $scale['min'],
                $scale['max'],
            );

            $dimensionIds = $session->version->dimensions()->pluck('id', 'code');

            foreach ($result['dimensions'] as $dimension) {
                AssessmentScore::query()->create([
                    'university_id' => $session->university_id,
                    'session_id' => $session->getKey(),
                    'dimension_id' => $dimensionIds[$dimension['code']],
                    'dimension_code' => $dimension['code'],
                    'item_count' => $dimension['item_count'],
                    'raw_score' => $dimension['raw_score'],
                    'mean_score' => $dimension['mean_score'],
                    'percent_score' => $dimension['percent_score'],
                    'rank' => $dimension['rank'],
                ]);
            }

            $session->forceFill([
                'status' => $target->value,
                'submitted_at' => now(),
                'holland_code' => $result['holland_code'],
                'dominant_dimensions' => $result['dominant'],
                'scoring_engine' => $result['engine'],
                'scored_at' => now(),
            ])->save();

            $this->event($session, $target === AssessmentSessionStatus::Submitted ? 'submitted' : 'expired', $actor, ['answered' => $total, 'total' => $total, 'scored' => true]);

            return $session->refresh();
        });
    }

    /** @return ?string kode alasan bila peserta belum boleh memulai, null bila boleh */
    private function problem(Application $application, AssessmentVersion $version, bool $consented): ?string
    {
        if ($application->submitted_at === null) {
            return 'APPLICATION_NOT_SUBMITTED';
        }

        if ($application->withdrawn_at !== null) {
            return 'APPLICATION_WITHDRAWN';
        }

        if ($version->status !== AssessmentVersionStatus::Published || $version->purpose !== AssessmentPurpose::Exploratory) {
            return 'NOT_AVAILABLE';
        }

        if ($version->period_id !== null && $version->period_id !== $application->period_id) {
            return 'PERIOD_MISMATCH';
        }

        $now = CarbonImmutable::now();

        if ($version->available_from !== null && $now->lt($version->available_from)) {
            return 'NOT_OPEN';
        }

        if ($version->available_until !== null && $now->gt($version->available_until)) {
            return 'CLOSED';
        }

        return $consented ? null : 'CONSENT_REQUIRED';
    }

    private function hasConsent(ApplicantAccount $account): bool
    {
        return AccountConsent::query()
            ->where('applicant_account_id', $account->getKey())
            ->where('consent_type', ConsentType::AssessmentTalent->value)
            ->whereNull('revoked_at')
            ->exists();
    }

    private function assertOpen(AssessmentSession $session): void
    {
        if ($session->status === AssessmentSessionStatus::Expired) {
            throw new DomainRuleException('Waktu pengerjaan telah habis.', 'SESSION_EXPIRED', 409);
        }

        if ($session->status !== AssessmentSessionStatus::InProgress) {
            throw new DomainRuleException('Asesmen sudah dikirim.', 'SESSION_CLOSED', 409);
        }
    }

    /** @return array{min: int, max: int} */
    private function scale(AssessmentVersion $version): array
    {
        $config = $version->scoring_config ?? [];

        return [
            'min' => (int) ($config['scale_min'] ?? AssessmentConfigurationService::SCALE_MIN),
            'max' => (int) ($config['scale_max'] ?? AssessmentConfigurationService::SCALE_MAX),
        ];
    }

    /** @param  array<string, mixed>|null  $payload */
    private function event(AssessmentSession $session, string $type, string $actor, ?array $payload = null): void
    {
        AssessmentSessionEvent::query()->create([
            'university_id' => $session->university_id,
            'session_id' => $session->getKey(),
            'event_type' => $type,
            'actor' => $actor,
            'payload' => $payload,
            'created_at' => now(),
        ]);
    }
}
