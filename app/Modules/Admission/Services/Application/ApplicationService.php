<?php

namespace Modules\Admission\Services\Application;

use App\Models\User;
use App\Support\Http\Exceptions\ConflictException;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Modules\Admission\Enums\ComponentSource;
use Modules\Admission\Enums\ConsentType;
use Modules\Admission\Enums\ContentStatus;
use Modules\Admission\Enums\ContentType;
use Modules\Admission\Enums\DocumentStage;
use Modules\Admission\Enums\FormStatus;
use Modules\Admission\Enums\Lifecycle;
use Modules\Admission\Enums\PathCategory;
use Modules\Admission\Enums\PaymentTiming;
use Modules\Admission\Enums\PeriodStatus;
use Modules\Admission\Enums\PmbInvoiceStatus;
use Modules\Admission\Enums\RegistrationPaymentStatus;
use Modules\Admission\Enums\SelectionStatus;
use Modules\Admission\Enums\VerificationStatus;
use Modules\Admission\Enums\WaveStatus;
use Modules\Admission\Exceptions\DomainRuleException;
use Modules\Admission\Exceptions\InvalidTransitionException;
use Modules\Admission\Models\AccountConsent;
use Modules\Admission\Models\ApplicantAccount;
use Modules\Admission\Models\ApplicantProfile;
use Modules\Admission\Models\Application;
use Modules\Admission\Models\ApplicationAnswer;
use Modules\Admission\Models\ApplicationChoice;
use Modules\Admission\Models\Content;
use Modules\Admission\Models\FormField;
use Modules\Admission\Models\PmbInvoice;
use Modules\Admission\Models\Quota;
use Modules\Admission\Models\SelectionComponent;
use Modules\Admission\Models\WavePath;
use Modules\Admission\Outbox\OutboxRecorder;
use Modules\Admission\Services\Account\ProfileService;
use Modules\Admission\Support\Actor;
use Modules\Admission\Support\CoreFormFields;
use Modules\Admission\Support\FormRuleEvaluator;
use Modules\Admission\Support\HistoryRecorder;
use Modules\Admission\Support\Money;
use Modules\Admission\Support\PmbSettings;
use Modules\Admission\Support\SequenceGenerator;
use Modules\Admission\Support\StateTransition;

/**
 * Formulir pendaftaran (docs/pmb/04 §D, SM-02): draft per gelombang-jalur
 * dengan snapshot konfigurasi, simpan per langkah dengan optimistic locking,
 * kelengkapan per langkah, kirim atomik & idempoten, revisi terbatas target.
 */
class ApplicationService
{
    public const STEPS = ['identity', 'education', 'choices', 'supporting', 'documents', 'payment', 'declarations'];

    public function __construct(
        private readonly SequenceGenerator $sequences,
        private readonly HistoryRecorder $history,
        private readonly OutboxRecorder $outbox,
        private readonly PmbSettings $settings,
        private readonly ProfileService $profiles,
        private readonly DocumentService $documents,
    ) {}

    // ------------------------------------------------------------------ Gelombang-jalur terbuka

    /**
     * 11 §A.5 `open-wave-paths`: gelombang-jalur yang dapat didaftar,
     * dengan flag kelayakan BR-04.
     *
     * @return array<int, array<string, mixed>>
     */
    public function openWavePaths(?ApplicantAccount $account): array
    {
        $now = now();
        $wavePaths = WavePath::query()->with(['wave.period', 'admissionPath'])
            ->where('is_open', true)
            ->whereHas('wave', fn ($q) => $q->where('status', WaveStatus::Open->value)->where('registration_opens_at', '<=', $now)->where('registration_closes_at', '>', $now)
                ->whereHas('period', fn ($p) => $p->where('status', PeriodStatus::Published->value)))
            ->get();

        return $wavePaths->map(function (WavePath $wavePath) use ($account): array {
            $reason = $account ? $this->eligibilityReason($account, $wavePath) : null;

            return [
                'wave_path_id' => $wavePath->getKey(),
                'wave' => ['id' => $wavePath->wave->getKey(), 'code' => $wavePath->wave->code, 'name' => $wavePath->wave->name, 'is_ods' => $wavePath->wave->is_ods],
                'period' => ['id' => $wavePath->wave->period->getKey(), 'code' => $wavePath->wave->period->code, 'name' => $wavePath->wave->period->name],
                'path' => ['id' => $wavePath->admissionPath->getKey(), 'code' => $wavePath->admissionPath->code, 'name' => $wavePath->admissionPath->name, 'category' => $wavePath->admissionPath->category->value, 'description' => $wavePath->admissionPath->description],
                'fee' => (string) $wavePath->registration_fee,
                'payment_timing' => $wavePath->payment_timing->value,
                'closes_at' => $wavePath->wave->registration_closes_at->toIso8601String(),
                'max_choices' => $wavePath->max_choices,
                'public_notes' => $wavePath->public_notes,
                'eligible' => $reason === null,
                'ineligible_reason' => $reason,
            ];
        })->values()->all();
    }

    /**
     * BR-04: satu aplikasi aktif per (identitas, gelombang-jalur); maks N per
     * periode; jalur lain dalam periode sama hanya bila diizinkan.
     */
    public function eligibilityReason(ApplicantAccount $account, WavePath $wavePath): ?string
    {
        $periodId = $wavePath->wave->period_id;
        $active = Application::query()->active()->where('applicant_account_id', $account->getKey())->where('period_id', $periodId)->get();

        if ($active->contains('wave_path_id', $wavePath->getKey())) {
            return 'Anda sudah memiliki pendaftaran aktif di gelombang & jalur ini.';
        }

        if ($active->count() >= max(1, $this->settings->int('pmb.max_active_applications_per_period'))) {
            return 'Batas jumlah pendaftaran aktif dalam satu periode sudah tercapai.';
        }

        if ($active->isNotEmpty() && ! $this->settings->bool('pmb.allow_multi_path_per_period')
            && $active->contains(fn (Application $a) => $a->admission_path_id !== $wavePath->admission_path_id)) {
            return 'Pendaftaran pada lebih dari satu jalur dalam satu periode tidak diizinkan.';
        }

        // Identitas yang sama (akun lain) tidak boleh punya aplikasi aktif pada gelombang-jalur yang sama.
        $identityHash = ApplicantProfile::query()->where('applicant_account_id', $account->getKey())->value('identity_hash');

        if ($identityHash !== null) {
            $sameIdentity = Application::query()->active()->where('wave_path_id', $wavePath->getKey())
                ->whereHas('account.profile', fn ($q) => $q->where('identity_hash', $identityHash))
                ->where('applicant_account_id', '!=', $account->getKey())->exists();

            if ($sameIdentity) {
                return 'Identitas Anda sudah memiliki pendaftaran pada gelombang & jalur ini.';
            }
        }

        return null;
    }

    // ------------------------------------------------------------------ Draft

    public function createDraft(ApplicantAccount $account, string $wavePathId, Actor $actor): Application
    {
        $wavePath = WavePath::query()->with(['wave.period', 'admissionPath'])->findOrFail($wavePathId);
        $this->assertWaveOpen($wavePath);

        return DB::transaction(function () use ($account, $wavePath, $actor): Application {
            // Lock baris akun agar dua request paralel tidak melewati batas BR-04.
            ApplicantAccount::query()->whereKey($account->getKey())->lockForUpdate()->first();

            $reason = $this->eligibilityReason($account, $wavePath);

            if ($reason !== null) {
                throw new DomainRuleException($reason, 'APPLICATION_LIMIT_REACHED', 409);
            }

            $wave = $wavePath->wave;
            $fee = (string) $wavePath->registration_fee;

            $application = Application::query()->create([
                'university_id' => $account->university_id,
                'applicant_account_id' => $account->getKey(),
                'period_id' => $wave->period_id,
                'wave_id' => $wave->getKey(),
                'wave_path_id' => $wavePath->getKey(),
                'admission_path_id' => $wavePath->admission_path_id,
                'form_template_id' => $wavePath->form_template_id,
                'form_status' => FormStatus::Draft,
                'verification_status' => VerificationStatus::NotStarted,
                'selection_status' => SelectionStatus::NotEligible,
                'registration_payment_status' => Money::isZero($fee) ? RegistrationPaymentStatus::NotRequired : RegistrationPaymentStatus::Unpaid,
                'config_snapshot' => [
                    'registration_fee' => $fee,
                    'payment_timing' => $wavePath->payment_timing->value,
                    'max_choices' => $wavePath->max_choices,
                    'allow_cross_system_choice' => $wavePath->allow_cross_system_choice,
                    'path_code' => $wavePath->admissionPath->code,
                    'path_category' => $wavePath->admissionPath->category->value,
                    'eligible_degree_levels' => $wavePath->admissionPath->eligible_degree_levels,
                    'wave_code' => $wave->code,
                    'period_code' => $wave->period->code,
                    'registration_closes_at' => $wave->registration_closes_at->toIso8601String(),
                    'payment_deadline_at' => $wave->payment_deadline_at->toIso8601String(),
                    'form_lock_at' => $wave->form_lock_at->toIso8601String(),
                    'announcement_at' => $wave->announcement_at->toIso8601String(),
                    'is_ods' => $wave->is_ods,
                ],
                'info_source' => $account->info_source,
                'created_by_user_id' => $actor->userId(),
            ]);

            $this->documents->syncRequirements($application, DocumentStage::Application);
            $this->history->record($application, Lifecycle::Form, null, FormStatus::Draft, $actor);
            $this->outbox->notifyApplicant($account, 'pmb.application.created', [
                'path_name' => $wavePath->admissionPath->name,
                'wave_name' => $wave->name,
                'deadline' => $wave->registration_closes_at->setTimezone($this->timezone())->format('d-m-Y H:i'),
            ], aggregate: $application);

            return $application->refresh();
        });
    }

    /**
     * Autosave per langkah (FR-FRM-07, 11 §A.5 PATCH): hanya draft, atau
     * field target saat `revision_requested`. Optimistic locking → 409.
     *
     * @param  array<string, mixed>  $data
     */
    public function saveSection(Application $application, string $section, array $data, int $lockVersion, Actor $actor): Application
    {
        return $this->mutate($application, $lockVersion, function (Application $application) use ($section, $data, $actor): void {
            $editable = $this->editableFields($application);

            match ($section) {
                'identity' => $this->profiles->saveProfile($application->account, $this->onlyEditable($data, $editable, 'identity'), null, $actor->isUser()),
                'education' => $this->saveEducationSection($application, $this->onlyEditable($data, $editable, 'education')),
                'supporting' => $application->forceFill($this->supportingValues($application, $this->onlyEditable($data, $editable, 'supporting'))),
                default => throw ValidationException::withMessages(['section' => 'Seksi tidak dikenal.']),
            };
        });
    }

    /**
     * Pilihan prodi (D.2.3): 1..max_choices, prioritas berurutan, kombinasi
     * harus ditawarkan (kuota is_offered), jenjang sesuai jalur, sistem kuliah
     * sama bila allow_cross_system_choice = false, tanpa duplikat.
     *
     * @param  array<int, array{priority: int, study_program_id: string, study_system_id: string}>  $choices
     */
    public function saveChoices(Application $application, array $choices, int $lockVersion, ?Actor $actor = null, ?string $reason = null): Application
    {
        $byAdmin = $actor?->isUser() ?? false;

        if ($byAdmin) {
            // BR-13: setelah submit hanya Admin PMB, dengan alasan, sebelum ranked.
            if ($application->selection_status === SelectionStatus::Ranked) {
                throw new ConflictException('Pilihan prodi tidak dapat diubah setelah hasil seleksi ditetapkan.', 'CONFIG_IN_USE');
            }

            if ($reason === null || trim($reason) === '') {
                throw ValidationException::withMessages(['reason' => 'Alasan wajib diisi.']);
            }
        }

        $snapshot = $application->config_snapshot;
        $max = (int) ($snapshot['max_choices'] ?? 2);
        $choices = array_values($choices);
        usort($choices, fn ($a, $b) => (int) $a['priority'] <=> (int) $b['priority']);

        if ($choices === [] || count($choices) > $max) {
            throw ValidationException::withMessages(['choices' => "Pilih 1–{$max} program studi."]);
        }

        $rows = [];
        $seen = [];
        $systems = [];

        foreach ($choices as $index => $choice) {
            if ((int) $choice['priority'] !== $index + 1) {
                throw ValidationException::withMessages(['choices' => 'Prioritas pilihan harus berurutan mulai dari 1.']);
            }

            $quota = Quota::query()->with('studyProgram')->where('period_id', $application->period_id)
                ->where('admission_path_id', $application->admission_path_id)
                ->where('study_program_id', $choice['study_program_id'])->where('study_system_id', $choice['study_system_id'])
                ->where('is_offered', true)->first();

            if ($quota === null) {
                throw ValidationException::withMessages(["choices.{$index}" => 'Program studi/sistem kuliah ini tidak ditawarkan pada jalur ini.']);
            }

            $levels = (array) ($snapshot['eligible_degree_levels'] ?? []);

            if ($levels !== [] && ! in_array($quota->studyProgram?->degree_level, $levels, true)) {
                throw ValidationException::withMessages(["choices.{$index}" => 'Jenjang program studi tidak sesuai jalur.']);
            }

            if (isset($seen[$quota->getKey()])) {
                throw ValidationException::withMessages(["choices.{$index}" => 'Program studi yang sama tidak boleh dipilih dua kali.']);
            }

            $seen[$quota->getKey()] = true;
            $systems[$choice['study_system_id']] = true;
            $rows[] = ['priority' => $index + 1, 'quota' => $quota];
        }

        if (count($systems) > 1 && ! ($snapshot['allow_cross_system_choice'] ?? false)) {
            throw ValidationException::withMessages(['choices' => 'Semua pilihan harus pada sistem kuliah yang sama.']);
        }

        $mutator = function (Application $application) use ($rows, $byAdmin, $reason, $actor): void {
            $old = $application->choices()->get()->map(fn ($c) => $c->study_program_id.'/'.$c->study_system_id)->all();
            ApplicationChoice::query()->where('application_id', $application->getKey())->delete();

            foreach ($rows as $row) {
                ApplicationChoice::query()->create([
                    'university_id' => $application->university_id,
                    'application_id' => $application->getKey(),
                    'priority' => $row['priority'],
                    'quota_id' => $row['quota']->getKey(),
                    'study_program_id' => $row['quota']->study_program_id,
                    'study_system_id' => $row['quota']->study_system_id,
                ]);
            }

            if ($byAdmin) {
                $this->history->change($application, ['choices' => $old], ['choices' => array_map(fn ($r) => $r['quota']->study_program_id.'/'.$r['quota']->study_system_id, $rows)], $reason);
            }
        };

        return $byAdmin ? $this->mutateByStaff($application, $lockVersion, $mutator) : $this->mutate($application, $lockVersion, $mutator, 'choices');
    }

    /**
     * Field tambahan (D.3): field tersembunyi dibuang & tidak divalidasi;
     * aturan Laravel dibangun dari definisi field.
     *
     * @param  array<string, mixed>  $answers
     */
    public function saveAnswers(Application $application, array $answers, int $lockVersion): Application
    {
        return $this->mutate($application, $lockVersion, function (Application $application) use ($answers): void {
            $fields = FormField::query()->where('form_template_id', $application->form_template_id)->where('stage', 'application')->get()->keyBy('key');
            $editable = $this->editableFields($application);

            foreach ($answers as $key => $value) {
                $field = $fields->get($key);

                if ($field === null) {
                    continue;
                }

                if ($editable !== null && ! in_array($key, $editable, true)) {
                    continue;
                }

                $this->validateAnswer($field, $value, partial: true);
                $this->storeAnswer($application, $field, $value);
            }
        }, 'answers');
    }

    // ------------------------------------------------------------------ Kelengkapan & kirim

    /**
     * Daftar kekurangan per langkah (11 §A.5 `completeness`, AC-03 "Belum lengkap").
     *
     * @return array{steps: array<int, array{key: string, complete: bool, missing: array<int, array<string, mixed>>}>, can_submit: bool}
     */
    public function completeness(Application $application): array
    {
        $application->loadMissing(['account.profile', 'choices', 'answers.field', 'formTemplate', 'admissionPath', 'primaryEducation.reportScores', 'wavePath.selectionScheme.components']);
        $profile = $application->account->profile;
        $rules = $application->formTemplate->core_field_rules ?? [];
        $context = $this->ruleContext($application);
        $missing = array_fill_keys(self::STEPS, []);
        $requirement = fn (string $field): string => CoreFormFields::requirement($field, $rules);
        $blank = fn ($value): bool => $value === null || $value === '' || $value === [];

        // Identitas
        foreach (CoreFormFields::FIELDS['identity'] as $field => $default) {
            $need = $requirement($field);
            $value = $profile?->{$field};
            $required = match ($field) {
                'nik' => ($profile?->nationality ?? 'ID') === 'ID' && $need !== 'hidden',
                'passport_number', 'passport_country' => ($profile?->nationality ?? 'ID') !== 'ID' && $need !== 'hidden',
                'gender' => $this->settings->bool('pmb.collect_gender') && $need !== 'hidden',
                default => $need === 'required',
            };

            if ($required && $blank($value)) {
                $missing['identity'][] = ['field' => $field];
            }
        }

        // Pendidikan
        $education = $application->primaryEducation;

        if ($education === null) {
            $missing['education'][] = ['field' => 'primary_education_id'];
        } else {
            $category = $application->admissionPath->category;

            if (in_array($category, [PathCategory::Transfer, PathCategory::Rpl, PathCategory::Postgraduate], true) && $education->level === 'secondary') {
                $missing['education'][] = ['field' => 'primary_education_id', 'message' => 'Jalur ini memerlukan riwayat pendidikan tinggi.'];
            }

            if (in_array($category, [PathCategory::Transfer, PathCategory::Postgraduate], true) && $education->gpa === null) {
                $missing['education'][] = ['field' => 'gpa'];
            }

            if ($education->level === 'secondary' && $blank($education->school_type)) {
                $missing['education'][] = ['field' => 'school_type'];
            }

            $reportComponent = $application->wavePath?->selectionScheme?->components->first(fn (SelectionComponent $c) => $c->source === ComponentSource::ReportCard);

            if ($reportComponent !== null && $requirement('report_scores') !== 'hidden') {
                $semesters = (array) ($reportComponent->config['semesters'] ?? [1, 2, 3, 4, 5]);
                $present = $education->reportScores->pluck('semester')->unique()->all();

                foreach ($semesters as $semester) {
                    if (! in_array($semester, $present, true)) {
                        $missing['education'][] = ['field' => 'report_scores', 'semester' => $semester];
                    }
                }
            }

            if ($requirement('nisn') === 'required' && $blank($education->nisn)) {
                $missing['education'][] = ['field' => 'nisn'];
            }
        }

        // Pilihan
        if ($application->choices->isEmpty()) {
            $missing['choices'][] = ['field' => 'choices'];
        }

        // Data pendukung
        $guardians = $application->account->guardians()->get();
        $age = $profile?->birth_date ? ProfileService::ageOn(CarbonImmutable::parse($profile->birth_date), CarbonImmutable::parse($application->period->starts_on)) : null;

        if ($guardians->isEmpty() || $guardians->every(fn ($g) => $blank($g->phone))) {
            $missing['supporting'][] = ['field' => 'guardians', 'message' => 'Minimal satu kontak darurat/orang tua dengan nomor HP.'];
        }

        if ($age !== null && $age < 18 && ! $guardians->contains(fn ($g) => in_array($g->relationship, ['father', 'mother', 'guardian'], true))) {
            $missing['supporting'][] = ['field' => 'guardians', 'message' => 'Pendaftar di bawah 18 tahun wajib mengisi data orang tua/wali.'];
        }

        if ($blank($application->info_source)) {
            $missing['supporting'][] = ['field' => 'info_source'];
        }

        if ($application->admissionPath->category === PathCategory::Achievement && $application->account->achievements()->doesntExist()) {
            $missing['supporting'][] = ['field' => 'achievements', 'message' => 'Jalur prestasi wajib mencantumkan minimal satu prestasi.'];
        }

        if ($application->admissionPath->category === PathCategory::KipAffirmation && $blank($application->kip_number) && $blank($application->kip_registration_number)) {
            $missing['supporting'][] = ['field' => 'kip_number'];
        }

        foreach (FormField::query()->where('form_template_id', $application->form_template_id)->where('stage', 'application')->get() as $field) {
            if (! FormRuleEvaluator::passes($field->visibility_rule, $context)) {
                continue;
            }

            $answer = $application->answers->firstWhere('form_field_id', $field->getKey());
            $value = $answer?->value_json ?? $answer?->value_text;

            if ($field->is_required && $blank($value)) {
                $missing[$this->stepForSection($field->section)][] = ['field' => $field->key, 'label' => $field->label];
            }
        }

        // Dokumen
        foreach ($this->documents->unmetMandatory($application, DocumentStage::Application, false, $context) as $doc) {
            $missing['documents'][] = ['document_type' => $doc['document_type'], 'name' => $doc['name'], 'status' => $doc['status']];
        }

        // Pembayaran
        if ($this->paymentRequiredBeforeSubmit($application) && ! $this->registrationPaid($application)) {
            $missing['payment'][] = ['field' => 'registration_fee', 'message' => 'Biaya pendaftaran belum lunas.'];
        }

        $steps = [];

        foreach (self::STEPS as $step) {
            if ($step === 'declarations') {
                continue;
            }

            $steps[] = ['key' => $step, 'complete' => $missing[$step] === [], 'missing' => $missing[$step]];
        }

        return ['steps' => $steps, 'can_submit' => collect($steps)->every(fn ($s) => $s['complete'])];
    }

    /**
     * Kirim formulir — urutan validasi 11 §A.18.1; satu transaksi: status,
     * nomor pendaftaran (pertama kali), snapshot profil, consent, history, outbox.
     * Idempotensi request ditangani middleware; transisi draft→submitted hanya sekali.
     *
     * @param  array<int, string>  $declarationIds
     */
    public function submit(Application $application, array $declarationIds, int $lockVersion, ?string $ip, ?string $userAgent, Actor $actor): Application
    {
        if (! in_array($application->form_status, [FormStatus::Draft, FormStatus::RevisionRequested], true)) {
            throw InvalidTransitionException::between('formulir', $application->form_status->value, FormStatus::Submitted->value);
        }

        $isRevision = $application->form_status === FormStatus::RevisionRequested;
        $wavePath = $application->wavePath()->with('wave')->first();

        if ($isRevision) {
            if ($application->revision_due_at !== null && $application->revision_due_at->isPast()) {
                throw new DomainRuleException('Batas waktu perbaikan telah lewat.', 'DEADLINE_PASSED', 422);
            }
        } else {
            $this->assertWaveOpen($wavePath);
        }

        $completeness = $this->completeness($application);

        if (! $completeness['can_submit']) {
            $missing = [];

            foreach ($completeness['steps'] as $step) {
                foreach ($step['missing'] as $item) {
                    $missing[] = ['step' => $step['key'], ...$item];
                }
            }

            $payment = collect($completeness['steps'])->firstWhere('key', 'payment');
            $onlyPayment = collect($completeness['steps'])->filter(fn ($s) => ! $s['complete'])->pluck('key')->all() === ['payment'];

            if ($onlyPayment && $payment !== null) {
                $invoiceId = PmbInvoice::query()->where('application_id', $application->getKey())->where('type', 'registration_fee')->whereNotIn('status', ['cancelled'])->value('id');

                throw new DomainRuleException('Biaya pendaftaran harus dibayar sebelum mengirim formulir.', 'PAYMENT_REQUIRED', 409, ['invoice_id' => $invoiceId]);
            }

            throw new DomainRuleException('Formulir belum lengkap.', 'INCOMPLETE_APPLICATION', 422, ['missing' => $missing]);
        }

        $requiredDeclarations = $this->activeDeclarations();
        $missingDeclarations = $requiredDeclarations->pluck('id')->diff($declarationIds)->values();

        if ($missingDeclarations->isNotEmpty()) {
            throw new DomainRuleException('Seluruh pernyataan wajib dicentang.', 'INCOMPLETE_APPLICATION', 422, ['missing' => $missingDeclarations->map(fn ($id) => ['step' => 'declarations', 'content_id' => $id])->all()]);
        }

        return DB::transaction(function () use ($application, $lockVersion, $declarationIds, $ip, $userAgent, $actor, $isRevision, $requiredDeclarations): Application {
            $application = Application::query()->whereKey($application->getKey())->lockForUpdate()->firstOrFail();
            $this->assertLockVersion($application, $lockVersion);

            $from = $application->form_status;
            StateTransition::assert($from, FormStatus::Submitted, 'formulir');
            $attributes = [
                'form_status' => FormStatus::Submitted,
                'submitted_at' => now(),
                'profile_snapshot' => $this->profileSnapshot($application),
                'declarations' => array_values($declarationIds),
                'revision_targets' => null,
                'revision_due_at' => null,
                'lock_version' => $application->lock_version + 1,
            ];

            if ($application->registration_number === null) {
                $snapshot = $application->config_snapshot;
                $number = $this->sequences->next('registration_number', $snapshot['period_code'].':'.$snapshot['path_code'], (string) $application->university_id);
                $attributes['registration_number'] = $snapshot['period_code'].'-'.$snapshot['path_code'].'-'.SequenceGenerator::pad($number, 6);
                $attributes['first_submitted_at'] = now();
            }

            $application->forceFill($attributes)->save();

            $account = $application->account;
            app(\Modules\Admission\Services\Account\AccountService::class)->recordConsent($account, ConsentType::DataProcessingPmb, null, null, $ip, $userAgent, $application->getKey());

            foreach ($requiredDeclarations as $declaration) {
                AccountConsent::query()->create([
                    'university_id' => $application->university_id,
                    'applicant_account_id' => $account->getKey(),
                    'application_id' => $application->getKey(),
                    'consent_type' => ConsentType::Declaration->value,
                    'content_id' => $declaration->getKey(),
                    'document_version' => $declaration->version,
                    'granted_at' => now(),
                    'ip_address' => $ip,
                    'user_agent' => $userAgent ? mb_substr($userAgent, 0, 500) : null,
                ]);
            }

            $this->history->record($application, Lifecycle::Form, $from, FormStatus::Submitted, $actor, $isRevision ? 'RESUBMISSION' : null);

            if ($isRevision && $application->verification_status === VerificationStatus::RevisionRequested) {
                $application->forceFill(['verification_status' => VerificationStatus::InReview])->save();
                $this->history->record($application, Lifecycle::Verification, VerificationStatus::RevisionRequested, VerificationStatus::InReview, $actor, 'RESUBMISSION');
            }

            if (! $isRevision) {
                $this->outbox->notifyApplicant($account, 'pmb.application.submitted', ['registration_number' => $application->registration_number], aggregate: $application);
            }

            return $application->refresh();
        });
    }

    /** FR: penarikan sebelum keputusan dipublikasikan (04 §D.4; BR-19 untuk biaya). */
    public function withdraw(Application $application, ?string $reason, Actor $actor): Application
    {
        if ($application->currentDecision()->published()->exists()) {
            throw new InvalidTransitionException('Keputusan sudah diumumkan; gunakan penolakan tawaran pada daftar ulang.');
        }

        return DB::transaction(function () use ($application, $reason, $actor): Application {
            $application = Application::query()->whereKey($application->getKey())->lockForUpdate()->firstOrFail();
            $from = $application->form_status;
            StateTransition::assert($from, FormStatus::Withdrawn, 'formulir');

            $application->forceFill([
                'form_status' => FormStatus::Withdrawn,
                'withdrawn_at' => now(),
                'withdraw_reason' => $reason ? mb_substr($reason, 0, 255) : null,
                'lock_version' => $application->lock_version + 1,
            ])->save();

            $this->cancelOpenInvoices($application);
            $application->activeAssignment()->update(['status' => 'released', 'active_key' => null]);
            $this->history->record($application, Lifecycle::Form, $from, FormStatus::Withdrawn, $actor, 'WITHDRAWN', $reason);

            if ($application->registration_number) {
                $this->outbox->notifyApplicant($application->account, 'pmb.application.withdrawn', ['registration_number' => $application->registration_number], aggregate: $application);
            }

            return $application->refresh();
        });
    }

    /** Hapus draft milik sendiri (→ void, 11 §A.5 DELETE). */
    public function discardDraft(Application $application, Actor $actor, string $reasonCode = 'DISCARDED'): Application
    {
        return DB::transaction(function () use ($application, $actor, $reasonCode): Application {
            $application = Application::query()->whereKey($application->getKey())->lockForUpdate()->firstOrFail();

            if ($application->form_status !== FormStatus::Draft) {
                throw InvalidTransitionException::between('formulir', $application->form_status->value, FormStatus::Void->value);
            }

            $application->forceFill(['form_status' => FormStatus::Void, 'lock_version' => $application->lock_version + 1])->save();
            $this->cancelOpenInvoices($application);
            $this->history->record($application, Lifecycle::Form, FormStatus::Draft, FormStatus::Void, $actor, $reasonCode);

            return $application->refresh();
        });
    }

    /**
     * Koreksi data oleh petugas (04 §D.4, `pmb_applications.correct`): alasan
     * wajib; nilai lama/baru diaudit. Koreksi NIK/nama setelah verified →
     * dokumen identitas wajib diverifikasi ulang.
     *
     * @param  array<string, mixed>  $changes
     */
    public function correctByStaff(Application $application, array $changes, string $reason, User $actor): Application
    {
        if (trim($reason) === '') {
            throw ValidationException::withMessages(['reason' => 'Alasan koreksi wajib diisi.']);
        }

        return DB::transaction(function () use ($application, $changes, $reason, $actor): Application {
            $profileChanges = Arr::only($changes, ['full_name', 'nationality', 'nik', 'passport_number', 'passport_country', 'birth_place', 'birth_date', 'gender', 'religion', 'mother_name', 'address_line', 'rt', 'rw', 'village', 'district', 'city', 'province', 'region_code', 'postal_code']);
            $applicationChanges = Arr::only($changes, ['info_source', 'info_source_detail', 'referral_code', 'kip_number', 'kip_registration_number', 'kip_status', 'scholarship_interest', 'scholarship_program_code']);
            $account = $application->account;

            if ($profileChanges !== []) {
                $profile = $this->profiles->profileFor($account);
                $old = [];

                foreach (array_keys($profileChanges) as $key) {
                    $old[$key] = in_array($key, ['nik', 'mother_name', 'passport_number'], true) ? '****' : $profile->{$key};
                }

                $this->profiles->saveProfile($account, $profileChanges, null, byStaff: true);
                $this->history->change($application, $old, array_map(fn ($v) => $v, Arr::except($profileChanges, ['nik', 'mother_name', 'passport_number'])) + array_fill_keys(array_intersect(array_keys($profileChanges), ['nik', 'mother_name', 'passport_number']), '****'), $reason);

                if ($application->verification_status === VerificationStatus::Verified && array_intersect(array_keys($profileChanges), ['full_name', 'nik', 'passport_number']) !== []) {
                    $identityDoc = $application->documents()->whereHas('documentType', fn ($q) => $q->where('code', 'ID_CARD'))->first();

                    if ($identityDoc !== null && $identityDoc->status->value === 'approved') {
                        $identityDoc->forceFill(['status' => 'in_review', 'locked_at' => null])->save();
                        $this->history->record($application, Lifecycle::Document, 'approved', 'in_review', Actor::user($actor), 'IDENTITY_CORRECTED', $reason, ['document_id' => $identityDoc->getKey()], $identityDoc);
                    }
                }

                if ($application->profile_snapshot !== null) {
                    $application->forceFill(['profile_snapshot' => $this->profileSnapshot($application->refresh())])->save();
                }
            }

            if ($applicationChanges !== []) {
                $old = $application->only(array_keys($applicationChanges));
                $application->forceFill($applicationChanges)->save();
                $this->history->change($application, Arr::except($old, ['kip_number']), Arr::except($applicationChanges, ['kip_number']), $reason);
            }

            return $application->refresh();
        });
    }

    // ------------------------------------------------------------------ Helper publik

    public function registrationPaid(Application $application): bool
    {
        return in_array($application->registration_payment_status, [RegistrationPaymentStatus::Paid, RegistrationPaymentStatus::NotRequired], true);
    }

    public function paymentRequiredBeforeSubmit(Application $application): bool
    {
        return ($application->config_snapshot['payment_timing'] ?? PaymentTiming::BeforeSubmit->value) === PaymentTiming::BeforeSubmit->value
            && ! Money::isZero($application->config_snapshot['registration_fee'] ?? '0');
    }

    /**
     * Konteks aturan kondisi field/dokumen.
     *
     * @return array<string, mixed>
     */
    public function ruleContext(Application $application): array
    {
        $application->loadMissing(['account.profile', 'admissionPath', 'answers.field']);
        $profile = $application->account->profile;
        $context = [
            'nationality' => $profile?->nationality ?? 'ID',
            'gender' => $profile?->gender,
            'has_achievement' => $application->account->achievements()->exists(),
            'path' => ['category' => $application->admissionPath->category->value, 'code' => $application->admissionPath->code],
            'path.category' => $application->admissionPath->category->value,
            'scholarship_interest' => $application->scholarship_interest,
            'info_source' => $application->info_source,
            'age' => $profile?->birth_date ? ProfileService::ageOn(CarbonImmutable::parse($profile->birth_date), CarbonImmutable::now()) : null,
        ];

        foreach ($application->answers as $answer) {
            if ($answer->field) {
                $context[$answer->field->key] = $answer->value_json ?? $answer->value_text;
            }
        }

        return $context;
    }

    /**
     * Field yang boleh diubah pendaftar: null = semua (draft); daftar target
     * saat `revision_requested`; [] = terkunci.
     *
     * @return array<int, string>|null
     */
    public function editableFields(Application $application): ?array
    {
        if ($application->form_status === FormStatus::Draft) {
            return null;
        }

        if ($application->form_status === FormStatus::RevisionRequested && ($application->revision_due_at === null || $application->revision_due_at->isFuture())) {
            return array_values((array) ($application->revision_targets['fields'] ?? []));
        }

        return [];
    }

    /** @return \Illuminate\Support\Collection<int, Content> */
    public function activeDeclarations(): \Illuminate\Support\Collection
    {
        return Content::query()->where('type', ContentType::Declaration->value)->where('status', ContentStatus::Published->value)
            ->orderBy('sort_order')->get()
            ->groupBy('slug')->map(fn ($versions) => $versions->sortByDesc('version')->first())->values();
    }

    private function assertWaveOpen(WavePath $wavePath): void
    {
        $wave = $wavePath->wave;
        $now = now();

        if (! $wavePath->is_open || $wave->status !== WaveStatus::Open || $now->lt($wave->registration_opens_at) || $now->gte($wave->registration_closes_at)) {
            throw new DomainRuleException('Pendaftaran gelombang ini sedang tidak dibuka.', 'WAVE_CLOSED', 422);
        }
    }

    private function assertLockVersion(Application $application, int $lockVersion): void
    {
        if ($application->lock_version !== $lockVersion) {
            throw new DomainRuleException('Data telah diubah di tab/perangkat lain. Muat ulang.', 'VERSION_CONFLICT', 409, ['current_version' => $application->lock_version]);
        }
    }

    /**
     * @param  callable(Application): void  $callback
     */
    private function mutate(Application $application, int $lockVersion, callable $callback, ?string $section = null): Application
    {
        return DB::transaction(function () use ($application, $lockVersion, $callback, $section): Application {
            $application = Application::query()->whereKey($application->getKey())->lockForUpdate()->firstOrFail();
            $this->assertLockVersion($application, $lockVersion);

            $editable = $this->editableFields($application);

            if ($editable === [] || ($section !== null && $editable !== null && ! in_array($section, $editable, true) && $section !== 'answers')) {
                throw new DomainRuleException('Formulir terkunci. Perubahan hanya dapat dilakukan pada bagian yang diminta panitia.', 'APPLICATION_LOCKED', 423);
            }

            $callback($application);
            $application->forceFill(['lock_version' => $application->lock_version + 1])->save();

            return $application->refresh();
        });
    }

    /**
     * @param  callable(Application): void  $callback
     */
    private function mutateByStaff(Application $application, int $lockVersion, callable $callback): Application
    {
        return DB::transaction(function () use ($application, $lockVersion, $callback): Application {
            $application = Application::query()->whereKey($application->getKey())->lockForUpdate()->firstOrFail();
            $this->assertLockVersion($application, $lockVersion);
            $callback($application);
            $application->forceFill(['lock_version' => $application->lock_version + 1])->save();

            return $application->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, string>|null  $editable
     * @return array<string, mixed>
     */
    private function onlyEditable(array $data, ?array $editable, string $section): array
    {
        if ($editable === null) {
            return $data;
        }

        return in_array($section, $editable, true) ? $data : Arr::only($data, $editable);
    }

    /** @param  array<string, mixed>  $data */
    private function saveEducationSection(Application $application, array $data): void
    {
        if (array_key_exists('primary_education_id', $data)) {
            $education = $application->account->educations()->whereKey($data['primary_education_id'])->first();

            if ($education === null) {
                throw ValidationException::withMessages(['primary_education_id' => 'Riwayat pendidikan tidak ditemukan.']);
            }

            $application->forceFill(['primary_education_id' => $education->getKey()]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function supportingValues(Application $application, array $data): array
    {
        $values = Arr::only($data, ['info_source', 'info_source_detail', 'referral_code', 'kip_number', 'kip_registration_number', 'scholarship_interest', 'scholarship_program_code']);

        Validator::make($values, [
            'info_source' => ['nullable', 'in:'.implode(',', CoreFormFields::INFO_SOURCES)],
            'info_source_detail' => ['nullable', 'string', 'max:255'],
            'referral_code' => ['nullable', 'string', 'max:50'],
            'kip_number' => ['nullable', 'string', 'max:40'],
            'kip_registration_number' => ['nullable', 'string', 'max:50'],
            'scholarship_interest' => ['nullable', 'boolean'],
            'scholarship_program_code' => ['nullable', 'string', 'max:50'],
        ])->validate();

        if (! empty($values['kip_number']) || ! empty($values['kip_registration_number'])) {
            $values['kip_status'] = $application->kip_status ?? 'claimed';
        }

        return $values;
    }

    private function validateAnswer(FormField $field, mixed $value, bool $partial): void
    {
        if ($value === null || $value === '' || $value === []) {
            return;
        }

        $validation = $field->validation ?? [];
        $rules = match ($field->type) {
            'number' => ['numeric'],
            'date' => ['date'],
            'email' => ['email'],
            'url' => ['url'],
            'phone' => ['regex:/^\+?[0-9]{8,15}$/'],
            'checkbox' => ['boolean'],
            'multiselect' => ['array'],
            'select', 'radio' => ['in:'.implode(',', array_map(fn ($o) => (string) $o['value'], (array) $field->options))],
            default => ['string', 'max:'.($field->type === 'textarea' ? 5000 : 500)],
        };

        foreach (['min' => 'min', 'max' => 'max', 'min_length' => 'min', 'max_length' => 'max', 'before' => 'before', 'after' => 'after'] as $key => $rule) {
            if (isset($validation[$key])) {
                $rules[] = $rule.':'.$validation[$key];
            }
        }

        if (isset($validation['regex']) && in_array($field->type, ['text', 'textarea'], true)) {
            $rules[] = 'regex:/'.str_replace('/', '\/', (string) $validation['regex']).'/u';
        }

        $data = ['value' => $value];
        $allRules = ['value' => $rules];

        if ($field->type === 'multiselect') {
            $allRules['value.*'] = ['in:'.implode(',', array_map(fn ($o) => (string) $o['value'], (array) $field->options))];
        }

        $validator = Validator::make($data, $allRules, [], ['value' => $field->label]);

        if ($validator->fails()) {
            throw ValidationException::withMessages(["answers.{$field->key}" => $validator->errors()->all()]);
        }
    }

    private function storeAnswer(Application $application, FormField $field, mixed $value): void
    {
        $isJson = is_array($value);
        $text = $isJson ? null : ($value === null ? null : (is_bool($value) ? ($value ? '1' : '0') : (string) $value));

        if ($field->is_sensitive && $text !== null) {
            $text = encrypt($text);
        }

        ApplicationAnswer::query()->updateOrCreate(
            ['application_id' => $application->getKey(), 'form_field_id' => $field->getKey()],
            ['university_id' => $application->university_id, 'value_text' => $text, 'value_json' => $isJson ? array_values($value) : null],
        );
    }

    private function stepForSection(string $section): string
    {
        return in_array($section, ['identity', 'education', 'choices', 'supporting'], true) ? $section : 'supporting';
    }

    /** @return array<string, mixed> */
    private function profileSnapshot(Application $application): array
    {
        $account = $application->account()->with(['profile', 'guardians', 'educations.reportScores', 'achievements'])->first();
        $profile = $account->profile;

        return [
            'captured_at' => now()->toIso8601String(),
            'account' => ['name' => $account->name, 'email' => $account->email, 'phone' => $account->phone],
            'profile' => $profile ? array_merge(Arr::except($profile->attributesToArray(), ['id', 'university_id', 'applicant_account_id', 'lock_version', 'created_at', 'updated_at']), [
                'nik' => $profile->nik, 'passport_number' => $profile->passport_number, 'mother_name' => $profile->mother_name,
                'special_needs' => $profile->special_needs, 'special_needs_note' => $profile->special_needs_note,
            ]) : null,
            'primary_education' => $application->primaryEducation?->load('reportScores')->toArray(),
            'guardians' => $account->guardians->map(fn ($g) => Arr::except($g->attributesToArray(), ['university_id', 'applicant_account_id']) + ['nik' => $g->nik])->all(),
            'achievements' => $account->achievements->map(fn ($a) => Arr::except($a->toArray(), ['university_id', 'applicant_account_id']))->all(),
        ];
    }

    private function cancelOpenInvoices(Application $application): void
    {
        PmbInvoice::query()->where('application_id', $application->getKey())
            ->whereIn('status', [PmbInvoiceStatus::Unpaid->value, PmbInvoiceStatus::Pending->value])->get()
            ->each(fn (PmbInvoice $invoice) => app(\Modules\Admission\Services\Payment\InvoiceService::class)->cancel($invoice, 'Aplikasi '.$application->form_status->value));
    }

    private function timezone(): string
    {
        return (string) (\Modules\Tenancy\Models\University::query()->whereKey(app(\App\Support\Tenancy\TenantContext::class)->universityId())->value('timezone') ?: config('app.timezone'));
    }
}
