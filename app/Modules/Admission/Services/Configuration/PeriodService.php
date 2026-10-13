<?php

namespace Modules\Admission\Services\Configuration;

use App\Models\User;
use App\Support\Http\Exceptions\ConflictException;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Academic\Enums\AcademicSemester;
use Modules\Academic\Models\AcademicTerm;
use Modules\Admission\Enums\ContentStatus;
use Modules\Admission\Enums\ContentType;
use Modules\Admission\Enums\DocumentStage;
use Modules\Admission\Enums\FormStatus;
use Modules\Admission\Enums\PeriodStatus;
use Modules\Admission\Enums\ReRegistrationStatus;
use Modules\Admission\Enums\RunStatus;
use Modules\Admission\Enums\SchemeStatus;
use Modules\Admission\Enums\TemplateStatus;
use Modules\Admission\Enums\WaveStatus;
use Modules\Admission\Models\Application;
use Modules\Admission\Models\ApplicationChoice;
use Modules\Admission\Models\Content;
use Modules\Admission\Models\DocumentRequirement;
use Modules\Admission\Models\FeeComponent;
use Modules\Admission\Models\FormTemplate;
use Modules\Admission\Models\Period;
use Modules\Admission\Models\Quota;
use Modules\Admission\Models\ReRegistration;
use Modules\Admission\Models\SelectionRun;
use Modules\Admission\Models\SelectionScheme;
use Modules\Admission\Models\StudentConversion;
use Modules\Admission\Models\Wave;
use Modules\Admission\Models\WavePath;
use Modules\Admission\Outbox\OutboxRecorder;
use Modules\Admission\Support\HistoryRecorder;
use Modules\Admission\Support\Money;
use Modules\AuditLog\Enums\AuditAction;

/**
 * Modul A — periode, gelombang, gelombang-jalur, kuota, persyaratan dokumen,
 * biaya (docs/pmb/03 §A.3–A.4). Semua perubahan tercatat di audit_logs.
 */
class PeriodService
{
    public function __construct(
        private readonly HistoryRecorder $history,
        private readonly OutboxRecorder $outbox,
    ) {}

    // ------------------------------------------------------------------ Periode

    /**
     * @param  array<string, mixed>  $data
     */
    public function createPeriod(array $data, User $actor): Period
    {
        $this->validatePeriodData($data);

        $period = Period::query()->create([
            ...Arr::only($data, ['entry_academic_term_id', 'code', 'name', 'starts_on', 'ends_on', 'description']),
            'status' => PeriodStatus::Draft,
            'created_by' => $actor->getKey(),
            'updated_by' => $actor->getKey(),
        ]);

        $this->history->change($period, [], $period->only(['code', 'name', 'starts_on', 'ends_on']), null, AuditAction::Created);

        return $period;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updatePeriod(Period $period, array $data, User $actor, ?string $reason): Period
    {
        if (in_array($period->status, [PeriodStatus::Closed, PeriodStatus::Archived], true)) {
            throw new ConflictException('Periode yang sudah ditutup/diarsipkan tidak dapat diubah.', 'CONFIG_IN_USE');
        }

        $data = Arr::only($data, ['entry_academic_term_id', 'code', 'name', 'starts_on', 'ends_on', 'description']);

        if ($this->periodHasSubmittedApplications($period)) {
            if (isset($data['entry_academic_term_id']) && $data['entry_academic_term_id'] !== $period->entry_academic_term_id) {
                throw new ConflictException('Tahun masuk tidak dapat diubah setelah ada pendaftar.', 'CONFIG_IN_USE');
            }

            $this->requireReason($reason);
        }

        $this->validatePeriodData([...$period->only(['entry_academic_term_id', 'starts_on', 'ends_on']), ...$data], $period);

        return $this->applyChange($period, [...$data, 'updated_by' => $actor->getKey()], $reason);
    }

    public function publishPeriod(Period $period, User $actor): Period
    {
        if ($period->status !== PeriodStatus::Draft) {
            throw new ConflictException('Hanya periode draft yang dapat dipublikasikan.', 'INVALID_TRANSITION');
        }

        $incomplete = $this->publishChecklist($period);

        if ($incomplete !== []) {
            throw ValidationException::withMessages(['incomplete' => $incomplete]);
        }

        return DB::transaction(function () use ($period, $actor): Period {
            $published = Period::query()->where('entry_academic_term_id', $period->entry_academic_term_id)
                ->where('status', PeriodStatus::Published->value)->whereKeyNot($period->getKey())->lockForUpdate()->exists();

            if ($published) {
                throw new ConflictException('Sudah ada periode terpublikasi untuk tahun masuk yang sama.', 'CONFIG_IN_USE');
            }

            $this->applyChange($period, ['status' => PeriodStatus::Published, 'published_at' => now(), 'updated_by' => $actor->getKey()], null);

            Wave::query()->where('period_id', $period->getKey())->where('status', WaveStatus::Draft->value)
                ->update(['status' => WaveStatus::Scheduled->value]);
            $this->refreshWaveStatuses($period);

            return $period->refresh();
        });
    }

    /**
     * Daftar periksa publikasi (FR-CFG-01 "Efek", ADM-10 dialog daftar periksa).
     *
     * @return array<int, string>
     */
    public function publishChecklist(Period $period): array
    {
        $missing = [];
        $waveIds = $period->waves()->pluck('id');

        if ($waveIds->isEmpty()) {
            $missing[] = 'Belum ada gelombang.';
        }

        $hasPublishedPath = WavePath::query()->whereIn('wave_id', $waveIds)
            ->whereHas('formTemplate', fn ($q) => $q->where('status', TemplateStatus::Published->value))->exists();

        if (! $hasPublishedPath) {
            $missing[] = 'Belum ada gelombang-jalur dengan template formulir terpublikasi.';
        }

        if (! Quota::query()->where('period_id', $period->getKey())->where('is_offered', true)->where('capacity', '>', 0)->exists()) {
            $missing[] = 'Belum ada kuota prodi yang ditawarkan.';
        }

        foreach ([ContentType::PrivacyPolicy, ContentType::Terms] as $legal) {
            if (! Content::query()->where('type', $legal->value)->where('status', ContentStatus::Published->value)->exists()) {
                $missing[] = 'Dokumen '.($legal === ContentType::PrivacyPolicy ? 'Kebijakan Privasi' : 'Ketentuan Pendaftaran').' belum dipublikasikan (BR-09).';
            }
        }

        foreach ($period->waves()->with('wavePaths.admissionPath')->get() as $wave) {
            foreach ($wave->wavePaths as $wavePath) {
                if (! DocumentRequirement::query()->where('wave_path_id', $wavePath->getKey())->exists()) {
                    $missing[] = "Persyaratan dokumen {$wavePath->admissionPath?->name} ({$wave->name}) belum diatur.";
                }
            }
        }

        return $missing;
    }

    public function closePeriod(Period $period, User|string|null $actor = null): Period
    {
        if ($period->status !== PeriodStatus::Published) {
            throw new ConflictException('Hanya periode terpublikasi yang dapat ditutup.', 'INVALID_TRANSITION');
        }

        return $this->applyChange($period, [
            'status' => PeriodStatus::Closed,
            'closed_at' => now(),
            'updated_by' => $actor instanceof User ? $actor->getKey() : null,
        ], null);
    }

    public function archivePeriod(Period $period, User $actor): Period
    {
        if ($period->status !== PeriodStatus::Closed) {
            throw new ConflictException('Hanya periode yang sudah ditutup yang dapat diarsipkan.', 'INVALID_TRANSITION');
        }

        $applicationIds = Application::query()->where('period_id', $period->getKey())->pluck('id');

        $openReRegistrations = ReRegistration::query()->whereIn('application_id', $applicationIds)
            ->whereIn('status', array_map(fn ($s) => $s->value, array_filter(ReRegistrationStatus::cases(), fn ($s) => $s->isActive())))->exists();
        $openConversions = StudentConversion::query()->whereIn('application_id', $applicationIds)
            ->whereIn('status', ['pending', 'processing', 'failed', 'manual_intervention'])->exists();

        if ($openReRegistrations || $openConversions) {
            throw new ConflictException('Masih ada daftar ulang atau konversi mahasiswa yang belum selesai.', 'CONFIG_IN_USE');
        }

        return $this->applyChange($period, ['status' => PeriodStatus::Archived, 'updated_by' => $actor->getKey()], null);
    }

    public function deletePeriod(Period $period): void
    {
        if ($period->status !== PeriodStatus::Draft || Application::query()->where('period_id', $period->getKey())->exists()) {
            throw new ConflictException('Hanya periode draft tanpa pendaftar yang dapat dihapus.', 'CONFIG_IN_USE');
        }

        DB::transaction(function () use ($period): void {
            $this->history->change($period, $period->only(['code', 'name']), [], null, AuditAction::Deleted);
            $period->delete();
        });
    }

    // ------------------------------------------------------------------ Gelombang

    /**
     * @param  array<string, mixed>  $data
     */
    public function createWave(Period $period, array $data, User $actor): Wave
    {
        if (in_array($period->status, [PeriodStatus::Closed, PeriodStatus::Archived], true)) {
            throw new ConflictException('Periode sudah ditutup.', 'CONFIG_IN_USE');
        }

        $attributes = $this->waveAttributes($data);
        $this->validateWaveDates($attributes, $period);

        $wave = Wave::query()->create([
            ...$attributes,
            'period_id' => $period->getKey(),
            'status' => $period->status === PeriodStatus::Published ? WaveStatus::Scheduled : WaveStatus::Draft,
            'created_by' => $actor->getKey(),
            'updated_by' => $actor->getKey(),
        ]);

        $this->history->change($wave, [], $attributes, null, AuditAction::Created);

        if ($period->status === PeriodStatus::Published) {
            $this->refreshWaveStatuses($period);
        }

        return $wave->refresh();
    }

    /**
     * Aturan dampak perubahan 03 §A.4 untuk tanggal gelombang.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateWave(Wave $wave, array $data, User $actor, ?string $reason): Wave
    {
        $attributes = $this->waveAttributes($data);
        $merged = [...$wave->only(array_keys($this->waveAttributes($wave->toArray()))), ...$attributes];
        $this->validateWaveDates($merged, $wave->period);

        if ($this->waveHasSubmittedApplications($wave)) {
            $this->requireReason($reason);

            if (isset($attributes['registration_closes_at'])
                && CarbonImmutable::parse($attributes['registration_closes_at'])->lt($wave->registration_closes_at)) {
                throw new ConflictException('Tanggal tutup pendaftaran tidak boleh dimajukan setelah ada pendaftar.', 'CONFIG_IN_USE');
            }

            if (array_key_exists('is_ods', $attributes) && (bool) $attributes['is_ods'] !== $wave->is_ods) {
                throw new ConflictException('Jenis gelombang ODS tidak dapat diubah setelah ada pendaftar.', 'CONFIG_IN_USE');
            }
        }

        $extended = isset($attributes['registration_closes_at'])
            && CarbonImmutable::parse($attributes['registration_closes_at'])->gt($wave->registration_closes_at);
        $examOrAnnouncementChanged = collect(['exam_starts_on', 'exam_ends_on', 'announcement_at'])
            ->contains(fn ($key) => array_key_exists($key, $attributes) && (string) $attributes[$key] !== (string) $wave->getRawOriginal($key));

        return DB::transaction(function () use ($wave, $attributes, $actor, $reason, $extended, $examOrAnnouncementChanged): Wave {
            $this->applyChange($wave, [...$attributes, 'updated_by' => $actor->getKey()], $reason);

            if ($extended) {
                $this->notifyDraftApplicants($wave, 'pmb.application.incomplete_reminder');
            }

            if ($examOrAnnouncementChanged && $this->waveHasSubmittedApplications($wave)) {
                $this->notifyParticipants($wave);
            }

            $this->refreshWaveStatuses($wave->period);

            return $wave->refresh();
        });
    }

    /**
     * Perpanjangan tanggal tutup (11 §A.9 `PATCH waves/{id}/extend`).
     */
    public function extendWave(Wave $wave, string $closesAt, User $actor, string $reason): Wave
    {
        $newClose = CarbonImmutable::parse($closesAt);

        if ($newClose->lte($wave->registration_closes_at)) {
            throw new ConflictException('Perpanjangan harus setelah tanggal tutup saat ini.', 'CONFIG_IN_USE');
        }

        $data = ['registration_closes_at' => $newClose];

        foreach (['payment_deadline_at', 'form_lock_at'] as $dependent) {
            if ($wave->{$dependent}->lt($newClose)) {
                $data[$dependent] = $newClose;
            }
        }

        if ($wave->status === WaveStatus::Closed) {
            $data['status'] = WaveStatus::Open;
        }

        return $this->updateWave($wave, $data, $actor, $reason);
    }

    public function deleteWave(Wave $wave): void
    {
        if (Application::query()->where('wave_id', $wave->getKey())->exists()) {
            throw new ConflictException('Gelombang yang sudah memiliki pendaftar tidak dapat dihapus.', 'CONFIG_IN_USE');
        }

        DB::transaction(function () use ($wave): void {
            $this->history->change($wave, $wave->only(['code', 'name']), [], null, AuditAction::Deleted);
            $wave->delete();
        });
    }

    /**
     * Transisi otomatis scheduled → open → closed berdasarkan tanggal;
     * announced oleh publikasi; completed setelah daftar ulang usai (FR-CFG-02).
     */
    public function refreshWaveStatuses(?Period $period = null): int
    {
        $changed = 0;
        $query = Wave::query()->whereIn('status', [WaveStatus::Scheduled->value, WaveStatus::Open->value, WaveStatus::Closed->value, WaveStatus::Announced->value]);

        if ($period !== null) {
            $query->where('period_id', $period->getKey());
        } else {
            $query->whereHas('period', fn ($q) => $q->where('status', PeriodStatus::Published->value));
        }

        foreach ($query->get() as $wave) {
            $now = now();
            $target = $wave->status;

            if ($wave->status === WaveStatus::Scheduled && $now->gte($wave->registration_opens_at)) {
                $target = WaveStatus::Open;
            }

            if (in_array($target, [WaveStatus::Open], true) && $now->gte($wave->registration_closes_at)) {
                $target = WaveStatus::Closed;
            }

            if (in_array($wave->status, [WaveStatus::Closed, WaveStatus::Announced], true) && $now->gte($wave->re_registration_closes_at)) {
                $pending = ReRegistration::query()
                    ->whereIn('application_id', Application::query()->where('wave_id', $wave->getKey())->select('id'))
                    ->whereIn('status', ['awaiting_confirmation', 'confirmed', 'in_progress', 'submitted', 'revision_requested', 'approved'])->exists();

                if (! $pending && $wave->status === WaveStatus::Announced) {
                    $target = WaveStatus::Completed;
                }
            }

            if ($target !== $wave->status) {
                $wave->forceFill(['status' => $target])->save();
                $changed++;
            }
        }

        return $changed;
    }

    // ------------------------------------------------------------------ Gelombang-jalur

    /**
     * @param  array<string, mixed>  $data
     */
    public function createWavePath(Wave $wave, array $data, User $actor): WavePath
    {
        $attributes = $this->wavePathAttributes($data);
        $this->validateWavePath($wave, $attributes);

        if (WavePath::query()->where('wave_id', $wave->getKey())->where('admission_path_id', $attributes['admission_path_id'])->exists()) {
            throw new ConflictException('Jalur ini sudah dibuka pada gelombang tersebut.', 'DUPLICATE');
        }

        $wavePath = WavePath::query()->create([...$attributes, 'wave_id' => $wave->getKey(), 'created_by' => $actor->getKey(), 'updated_by' => $actor->getKey()]);
        $this->history->change($wavePath, [], $attributes, null, AuditAction::Created);

        return $wavePath->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateWavePath(WavePath $wavePath, array $data, User $actor, ?string $reason): WavePath
    {
        $attributes = $this->wavePathAttributes($data);
        unset($attributes['admission_path_id']);
        $this->validateWavePath($wavePath->wave, [...$wavePath->only(['admission_path_id']), ...$attributes], $wavePath);

        if ($this->wavePathHasSubmittedApplications($wavePath)) {
            $this->requireReason($reason);

            if (array_key_exists('registration_fee', $attributes) && Money::compare($attributes['registration_fee'], $wavePath->registration_fee) !== 0) {
                throw new ConflictException('Biaya pendaftaran tidak dapat diubah setelah ada pendaftar (BR-03).', 'CONFIG_IN_USE');
            }

            foreach (['payment_timing', 'max_choices', 'allow_cross_system_choice'] as $locked) {
                if (array_key_exists($locked, $attributes) && $this->scalar($attributes[$locked]) !== $this->scalar($wavePath->{$locked})) {
                    throw new ConflictException('Aturan pendaftaran tidak dapat diubah setelah ada pendaftar (BR-03).', 'CONFIG_IN_USE');
                }
            }
        }

        if (array_key_exists('selection_scheme_id', $attributes) && $attributes['selection_scheme_id'] !== $wavePath->selection_scheme_id
            && SelectionRun::query()->where('wave_path_id', $wavePath->getKey())->whereNotIn('status', [RunStatus::Draft->value, RunStatus::Failed->value, RunStatus::Superseded->value])->exists()) {
            throw new ConflictException('Skema seleksi tidak dapat diganti setelah ada hasil perhitungan (03 §A.4).', 'CONFIG_IN_USE');
        }

        return $this->applyChange($wavePath, [...$attributes, 'updated_by' => $actor->getKey()], $reason);
    }

    public function toggleWavePath(WavePath $wavePath, bool $isOpen, User $actor, string $reason): WavePath
    {
        return $this->applyChange($wavePath, ['is_open' => $isOpen, 'updated_by' => $actor->getKey()], $reason);
    }

    public function deleteWavePath(WavePath $wavePath): void
    {
        if (Application::query()->where('wave_path_id', $wavePath->getKey())->exists()) {
            throw new ConflictException('Gelombang-jalur yang sudah memiliki pendaftar tidak dapat dihapus.', 'CONFIG_IN_USE');
        }

        DB::transaction(function () use ($wavePath): void {
            $this->history->change($wavePath, $wavePath->only(['wave_id', 'admission_path_id']), [], null, AuditAction::Deleted);
            $wavePath->delete();
        });
    }

    // ------------------------------------------------------------------ Kuota

    /**
     * Upsert matriks kuota (11 §A.9 `PUT quotas/bulk`, ADM-13).
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, Quota>
     */
    public function upsertQuotas(Period $period, array $rows, User $actor, ?string $reason): array
    {
        $opened = Wave::query()->where('period_id', $period->getKey())->whereNotIn('status', [WaveStatus::Draft->value, WaveStatus::Scheduled->value])->exists();

        if ($opened) {
            $this->requireReason($reason);
        }

        return DB::transaction(function () use ($period, $rows, $actor, $reason): array {
            $saved = [];

            foreach ($rows as $row) {
                $keys = [
                    'period_id' => $period->getKey(),
                    'admission_path_id' => $row['admission_path_id'],
                    'study_program_id' => $row['study_program_id'],
                    'study_system_id' => $row['study_system_id'],
                ];
                $quota = Quota::query()->where($keys)->lockForUpdate()->first();
                $values = Arr::only($row, ['capacity', 'waitlist_capacity', 'min_final_score', 'wave_caps', 'is_offered']);

                if ($quota !== null) {
                    if (isset($values['capacity']) && (int) $values['capacity'] < $quota->accepted_count) {
                        throw new ConflictException('Kapasitas tidak boleh di bawah jumlah yang sudah diterima ('.$quota->accepted_count.').', 'CONFIG_IN_USE');
                    }

                    if (array_key_exists('is_offered', $values) && ! $values['is_offered'] && $quota->is_offered
                        && ApplicationChoice::query()->where('quota_id', $quota->getKey())->exists()) {
                        throw new ConflictException('Prodi sudah dipilih pendaftar; tutup pendaftaran alih-alih menonaktifkan penawaran.', 'CONFIG_IN_USE');
                    }

                    $saved[] = $this->applyChange($quota, [...$values, 'updated_by' => $actor->getKey(), 'lock_version' => $quota->lock_version + 1], $reason);
                } else {
                    $quota = Quota::query()->create([...$keys, ...$values, 'accepted_count' => 0, 'created_by' => $actor->getKey(), 'updated_by' => $actor->getKey()]);
                    $this->history->change($quota, [], $quota->only(['capacity', 'study_program_id']), $reason, AuditAction::Created);
                    $saved[] = $quota;
                }
            }

            return $saved;
        });
    }

    public function copyQuotas(Period $target, Period $source, User $actor): int
    {
        $rows = Quota::query()->where('period_id', $source->getKey())->get()
            ->map(fn (Quota $q) => $q->only(['admission_path_id', 'study_program_id', 'study_system_id', 'capacity', 'waitlist_capacity', 'min_final_score', 'is_offered']))
            ->all();

        return count($this->upsertQuotas($target, $rows, $actor, 'Salin kuota dari periode '.$source->code));
    }

    // ------------------------------------------------------------------ Persyaratan dokumen

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function syncDocumentRequirements(WavePath $wavePath, array $rows, User $actor, ?string $reason): void
    {
        $hasSubmitted = $this->wavePathHasSubmittedApplications($wavePath);
        $announcementPassed = now()->gte($wavePath->wave->announcement_at);

        DB::transaction(function () use ($wavePath, $rows, $hasSubmitted, $announcementPassed, $reason): void {
            $existing = DocumentRequirement::query()->where('wave_path_id', $wavePath->getKey())->get()
                ->keyBy(fn (DocumentRequirement $r) => $r->document_type_id.'|'.$r->stage->value);
            $incoming = collect($rows)->keyBy(fn ($r) => $r['document_type_id'].'|'.$r['stage']);

            foreach ($incoming as $key => $row) {
                $current = $existing->get($key);
                $stage = DocumentStage::from($row['stage']);
                $isMandatory = (bool) ($row['is_mandatory'] ?? true);
                $newlyMandatory = $isMandatory && ($current === null || ! $current->is_mandatory);

                if ($hasSubmitted && $newlyMandatory) {
                    if ($stage === DocumentStage::Application) {
                        throw new ConflictException('Dokumen wajib tahap pendaftaran tidak dapat ditambah setelah ada pendaftar (03 §A.4).', 'CONFIG_IN_USE');
                    }

                    if ($announcementPassed) {
                        throw new ConflictException('Dokumen wajib daftar ulang hanya dapat ditambah sebelum pengumuman.', 'CONFIG_IN_USE');
                    }
                }

                $values = [
                    'is_mandatory' => $isMandatory,
                    'condition' => $row['condition'] ?? null,
                    'instructions' => $row['instructions'] ?? null,
                    'sort_order' => (int) ($row['sort_order'] ?? 0),
                ];

                if ($current) {
                    $this->applyChange($current, $values, $reason);
                } else {
                    DocumentRequirement::query()->create([...$values, 'wave_path_id' => $wavePath->getKey(), 'document_type_id' => $row['document_type_id'], 'stage' => $stage]);
                }
            }

            foreach ($existing as $key => $requirement) {
                if (! $incoming->has($key)) {
                    if ($hasSubmitted && $requirement->stage === DocumentStage::Application) {
                        throw new ConflictException('Persyaratan tahap pendaftaran tidak dapat dihapus setelah ada pendaftar.', 'CONFIG_IN_USE');
                    }

                    $this->history->change($requirement, $requirement->only(['document_type_id', 'stage']), [], $reason, AuditAction::Deleted);
                    $requirement->delete();
                }
            }
        });
    }

    // ------------------------------------------------------------------ Biaya

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function upsertFeeComponents(Period $period, array $rows, User $actor, ?string $reason): void
    {
        DB::transaction(function () use ($period, $rows, $actor, $reason): void {
            foreach ($rows as $row) {
                $plan = $row['installment_plan'] ?? null;

                if (! empty($row['installable'])) {
                    $total = array_sum(array_map(fn ($term) => (float) ($term['percent'] ?? 0), (array) $plan));

                    if (abs($total - 100) > 0.001 || empty($plan)) {
                        throw ValidationException::withMessages(['installment_plan' => 'Total persentase rencana cicilan harus 100%.']);
                    }
                }

                $keys = [
                    'period_id' => $period->getKey(),
                    'study_program_id' => $row['study_program_id'],
                    'study_system_id' => $row['study_system_id'],
                    'admission_path_id' => $row['admission_path_id'] ?? null,
                    'component_code' => strtoupper((string) $row['component_code']),
                ];
                $values = [
                    'name' => $row['name'],
                    'amount' => Money::fromCents(Money::toCents($row['amount'])),
                    'is_mandatory_for_enrollment' => (bool) ($row['is_mandatory_for_enrollment'] ?? true),
                    'installable' => (bool) ($row['installable'] ?? false),
                    'installment_plan' => ! empty($row['installable']) ? array_values($plan) : null,
                    'is_published' => (bool) ($row['is_published'] ?? true),
                    'sort_order' => (int) ($row['sort_order'] ?? 0),
                ];

                // NULL admission_path_id tidak ditahan unique DB → cari eksplisit (10 §4.1 catatan).
                $existing = FeeComponent::query()->where($keys)->lockForUpdate()->first();

                if ($existing) {
                    $this->applyChange($existing, [...$values, 'updated_by' => $actor->getKey()], $reason);
                } else {
                    $fee = FeeComponent::query()->create([...$keys, ...$values, 'created_by' => $actor->getKey(), 'updated_by' => $actor->getKey()]);
                    $this->history->change($fee, [], $values, $reason, AuditAction::Created);
                }
            }
        });
    }

    public function deleteFeeComponent(FeeComponent $fee, ?string $reason): void
    {
        DB::transaction(function () use ($fee, $reason): void {
            $this->history->change($fee, $fee->only(['component_code', 'amount']), [], $reason, AuditAction::Deleted);
            $fee->delete();
        });
    }

    public function copyFeeComponents(Period $target, Period $source, User $actor): int
    {
        $rows = FeeComponent::query()->where('period_id', $source->getKey())->get()
            ->map(fn (FeeComponent $f) => [...$f->only(['study_program_id', 'study_system_id', 'admission_path_id', 'component_code', 'name', 'is_mandatory_for_enrollment', 'installable', 'installment_plan', 'is_published', 'sort_order']), 'amount' => (string) $f->amount])
            ->all();
        $this->upsertFeeComponents($target, $rows, $actor, 'Salin biaya dari periode '.$source->code);

        return count($rows);
    }

    /**
     * Komponen biaya awal yang berlaku untuk (periode, prodi, sistem, jalur):
     * baris jalur spesifik menimpa baris umum per component_code (FR-CFG-09).
     *
     * @return array<string, FeeComponent>
     */
    public function effectiveFeeComponents(string $periodId, string $programId, string $systemId, ?string $pathId): array
    {
        $rows = FeeComponent::query()->where('period_id', $periodId)->where('study_program_id', $programId)->where('study_system_id', $systemId)
            ->where(fn ($q) => $q->whereNull('admission_path_id')->orWhere('admission_path_id', $pathId))
            ->orderBy('sort_order')->get();

        $effective = [];

        foreach ($rows as $row) {
            if (! isset($effective[$row->component_code]) || $row->admission_path_id !== null) {
                $effective[$row->component_code] = $row;
            }
        }

        return $effective;
    }

    // ------------------------------------------------------------------ Helper

    public function waveHasSubmittedApplications(Wave $wave): bool
    {
        return Application::query()->where('wave_id', $wave->getKey())->whereNotNull('first_submitted_at')->exists();
    }

    public function wavePathHasSubmittedApplications(WavePath $wavePath): bool
    {
        return Application::query()->where('wave_path_id', $wavePath->getKey())->whereNotNull('first_submitted_at')->exists();
    }

    public function periodHasSubmittedApplications(Period $period): bool
    {
        return Application::query()->where('period_id', $period->getKey())->whereNotNull('first_submitted_at')->exists();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function validatePeriodData(array $data, ?Period $period = null): void
    {
        $starts = CarbonImmutable::parse($data['starts_on']);
        $ends = CarbonImmutable::parse($data['ends_on']);

        if ($ends->lte($starts)) {
            throw ValidationException::withMessages(['ends_on' => 'Tanggal selesai harus setelah tanggal mulai.']);
        }

        $term = AcademicTerm::query()->find($data['entry_academic_term_id']);

        if ($term === null) {
            throw ValidationException::withMessages(['entry_academic_term_id' => 'Semester masuk tidak ditemukan. Buat terlebih dahulu di modul Akademik.']);
        }

        if ($term->semester !== AcademicSemester::Ganjil && ! ($data['allow_even_semester'] ?? false)) {
            throw ValidationException::withMessages(['entry_academic_term_id' => 'Tahun masuk PMB harus semester ganjil.']);
        }

        if (isset($data['code'])) {
            $duplicate = Period::query()->where('code', $data['code'])->when($period, fn ($q) => $q->whereKeyNot($period->getKey()))->exists();

            if ($duplicate) {
                throw ValidationException::withMessages(['code' => 'Kode periode sudah dipakai.']);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function waveAttributes(array $data): array
    {
        return Arr::only($data, [
            'code', 'name', 'sequence', 'registration_opens_at', 'registration_closes_at', 'payment_deadline_at', 'form_lock_at',
            'exam_starts_on', 'exam_ends_on', 'announcement_at', 're_registration_opens_at', 're_registration_closes_at', 'is_ods', 'status',
        ]);
    }

    /**
     * BR-02: urutan tanggal dalam gelombang + rentang periode.
     *
     * @param  array<string, mixed>  $d
     */
    private function validateWaveDates(array $d, Period $period): void
    {
        $t = fn (string $key) => isset($d[$key]) ? CarbonImmutable::parse($d[$key]) : null;
        $errors = [];
        $opens = $t('registration_opens_at');
        $closes = $t('registration_closes_at');
        $payment = $t('payment_deadline_at');
        $lock = $t('form_lock_at');
        $examStart = $t('exam_starts_on');
        $examEnd = $t('exam_ends_on');
        $announce = $t('announcement_at');
        $reOpen = $t('re_registration_opens_at');
        $reClose = $t('re_registration_closes_at');
        $isOds = (bool) ($d['is_ods'] ?? false);

        if ($opens && $opens->lt(CarbonImmutable::parse($period->starts_on)->startOfDay())) {
            $errors['registration_opens_at'] = 'Pembukaan pendaftaran tidak boleh sebelum periode dimulai.';
        }

        if ($opens && $closes && ! $closes->gt($opens)) {
            $errors['registration_closes_at'] = 'Tanggal tutup harus setelah tanggal buka.';
        }

        if ($closes && $payment && $payment->lt($closes)) {
            $errors['payment_deadline_at'] = 'Batas bayar tidak boleh sebelum tanggal tutup pendaftaran.';
        }

        if ($payment && $lock && $lock->lt($payment)) {
            $errors['form_lock_at'] = 'Batas perbaikan formulir tidak boleh sebelum batas bayar.';
        }

        if ($examStart && $examEnd && $examEnd->lt($examStart)) {
            $errors['exam_ends_on'] = 'Akhir ujian tidak boleh sebelum awal ujian.';
        }

        if (! $isOds && $lock && $examStart && $examStart->lt($lock->startOfDay())) {
            $errors['exam_starts_on'] = 'Ujian tidak boleh sebelum batas perbaikan formulir.';
        }

        $afterExam = $examEnd ?? $lock;

        if ($announce && $afterExam && $announce->lt($afterExam->startOfDay())) {
            $errors['announcement_at'] = 'Pengumuman tidak boleh sebelum ujian selesai.';
        }

        if ($announce && $reOpen && $reOpen->lt($announce)) {
            $errors['re_registration_opens_at'] = 'Daftar ulang tidak boleh dibuka sebelum pengumuman.';
        }

        if ($reOpen && $reClose && ! $reClose->gt($reOpen)) {
            $errors['re_registration_closes_at'] = 'Tutup daftar ulang harus setelah dibuka.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function wavePathAttributes(array $data): array
    {
        $attributes = Arr::only($data, [
            'admission_path_id', 'registration_fee', 'payment_timing', 'max_choices', 'allow_cross_system_choice',
            'form_template_id', 'selection_scheme_id', 'quota_mode', 'bulk_verifiable', 'is_open', 'public_notes',
        ]);

        if (isset($attributes['registration_fee'])) {
            $attributes['registration_fee'] = Money::fromCents(Money::toCents($attributes['registration_fee']));
        }

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function validateWavePath(Wave $wave, array $attributes, ?WavePath $current = null): void
    {
        if (isset($attributes['form_template_id'])) {
            $template = FormTemplate::query()->find($attributes['form_template_id']);

            if ($template === null || $template->status !== TemplateStatus::Published) {
                throw ValidationException::withMessages(['form_template_id' => 'Template formulir harus berstatus published.']);
            }
        }

        if (! empty($attributes['selection_scheme_id'])) {
            $scheme = SelectionScheme::query()->find($attributes['selection_scheme_id']);

            if ($scheme === null || $scheme->status !== SchemeStatus::Locked) {
                throw ValidationException::withMessages(['selection_scheme_id' => 'Skema seleksi harus berstatus locked.']);
            }
        }

        if (isset($attributes['max_choices']) && ((int) $attributes['max_choices'] < 1 || (int) $attributes['max_choices'] > 5)) {
            throw ValidationException::withMessages(['max_choices' => 'Jumlah pilihan prodi 1–5.']);
        }

        // BR-02: gelombang untuk jalur yang sama dalam satu periode tidak boleh tumpang tindih.
        $pathId = $attributes['admission_path_id'] ?? $current?->admission_path_id;
        $overlap = WavePath::query()->where('admission_path_id', $pathId)
            ->when($current, fn ($q) => $q->whereKeyNot($current->getKey()))
            ->whereHas('wave', fn ($q) => $q->where('period_id', $wave->period_id)->whereKeyNot($wave->getKey())
                ->where('registration_opens_at', '<', $wave->registration_closes_at)
                ->where('registration_closes_at', '>', $wave->registration_opens_at))
            ->exists();

        if ($overlap) {
            throw ValidationException::withMessages(['admission_path_id' => 'Jalur ini sudah dibuka pada gelombang lain yang jadwalnya tumpang tindih (BR-02).']);
        }
    }

    private function requireReason(?string $reason): void
    {
        if ($reason === null || mb_strlen(trim($reason)) < 5) {
            throw ValidationException::withMessages(['reason' => 'Alasan perubahan wajib diisi karena pendaftaran sudah berjalan.']);
        }
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  TModel  $model
     * @param  array<string, mixed>  $attributes
     * @return TModel
     */
    private function applyChange($model, array $attributes, ?string $reason)
    {
        return DB::transaction(function () use ($model, $attributes, $reason) {
            $model->forceFill($attributes);
            $dirty = $model->getDirty();
            unset($dirty['updated_at'], $dirty['updated_by'], $dirty['lock_version']);

            $old = [];

            foreach (array_keys($dirty) as $key) {
                $old[$key] = $model->getOriginal($key);
            }

            $model->save();

            if ($dirty !== []) {
                $this->history->change($model, $this->serializable($old), $this->serializable(Arr::only($model->getAttributes(), array_keys($dirty))), $reason);
            }

            return $model->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function serializable(array $values): array
    {
        return array_map(fn ($value) => $value instanceof \BackedEnum ? $value->value : ($value instanceof \DateTimeInterface ? $value->format(DATE_ATOM) : $value), $values);
    }

    private function scalar(mixed $value): string
    {
        return $value instanceof \BackedEnum ? (string) $value->value : (is_bool($value) ? ($value ? '1' : '0') : (string) $value);
    }

    private function notifyDraftApplicants(Wave $wave, string $eventKey): void
    {
        Application::query()->with('account', 'admissionPath')->where('wave_id', $wave->getKey())
            ->where('form_status', FormStatus::Draft->value)->get()
            ->each(fn (Application $a) => $this->outbox->notifyApplicant($a->account, $eventKey, [
                'path_name' => $a->admissionPath?->name,
                'deadline' => $wave->registration_closes_at->toDateTimeString(),
            ], aggregate: $a));
    }

    private function notifyParticipants(Wave $wave): void
    {
        Application::query()->with('account')->where('wave_id', $wave->getKey())
            ->whereNotNull('submitted_at')->whereNotIn('form_status', [FormStatus::Withdrawn->value, FormStatus::Void->value])->get()
            ->each(fn (Application $a) => $this->outbox->notifyApplicant($a->account, 'pmb.exam.rescheduled', [
                'exam_title' => 'Jadwal seleksi '.$wave->name,
                'exam_date' => optional($wave->exam_starts_on)->toDateString() ?? '-',
                'exam_location' => 'lihat portal',
            ], aggregate: $a));
    }
}
