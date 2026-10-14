<?php

namespace Modules\Admission\Services\Conversion;

use App\Models\User;
use App\Support\Http\Exceptions\ConflictException;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Academic\Models\Student;
use Modules\Academic\Services\AdmissionEnrollmentData;
use Modules\Academic\Services\AdmissionEnrollmentException;
use Modules\Academic\Services\AdmissionEnrollmentService;
use Modules\Admission\Enums\ConversionStatus;
use Modules\Admission\Enums\DocumentStatus;
use Modules\Admission\Enums\InvoiceType;
use Modules\Admission\Enums\Lifecycle;
use Modules\Admission\Enums\ReRegistrationStatus;
use Modules\Admission\Enums\TransactionStatus;
use Modules\Admission\Models\AdmissionDecision;
use Modules\Admission\Models\ApplicationDocument;
use Modules\Admission\Models\PmbInvoice;
use Modules\Admission\Models\StudentConversion;
use Modules\Admission\Outbox\OutboxRecorder;
use Modules\Admission\Support\Actor;
use Modules\Admission\Support\HistoryRecorder;
use Modules\Admission\Support\Money;
use Modules\Admission\Support\NimFormatter;
use Modules\Admission\Support\PmbPermission;
use Modules\Admission\Support\PmbSettings;
use Modules\Admission\Support\SequenceGenerator;
use Modules\FileManagement\Models\FileUpload;
use Modules\Finance\Services\AdmissionPostingService;
use Modules\Scholarship\Enums\ScholarshipApplicationStatus;
use Modules\Scholarship\Models\ScholarshipApplication;
use Throwable;

/**
 * Pembentukan mahasiswa (docs/pmb/07 §K.6–K.7, SM-11, KD-06): worker
 * idempoten dengan lease, langkah tercatat (`steps_completed`), NIM dari
 * sequence ber-lock DI TRANSAKSI YANG SAMA dengan insert `students` (rollback
 * mengembalikan nomor), retry eksponensial untuk galat sementara, `failed`
 * untuk galat data, `manual_intervention` bila retry habis.
 */
class ConversionService
{
    public const STEPS = ['student_created', 'finance_posted', 'documents_linked', 'scholarship_forwarded'];

    private const BACKOFF_MINUTES = [1, 5, 15, 60, 60, 60];

    private const MAX_ATTEMPTS = 6;

    private const LEASE_MINUTES = 5;

    public function __construct(
        private readonly SequenceGenerator $sequences,
        private readonly AdmissionEnrollmentService $enrollment,
        private readonly AdmissionPostingService $posting,
        private readonly HistoryRecorder $history,
        private readonly OutboxRecorder $outbox,
        private readonly PmbSettings $settings,
        private readonly TenantContext $tenant,
    ) {}

    /** Scheduler: konversi pending yang jatuh tempo + lease kedaluwarsa. */
    public function processPending(int $limit = 20): int
    {
        $count = 0;
        $ids = StudentConversion::query()->withoutGlobalScopes()
            ->where(fn ($q) => $q->where('status', ConversionStatus::Pending->value)->where(fn ($w) => $w->whereNull('next_attempt_at')->orWhere('next_attempt_at', '<=', now())))
            ->orWhere(fn ($q) => $q->where('status', ConversionStatus::Processing->value)->where('locked_until', '<', now()))
            ->limit($limit)->pluck('id');

        foreach ($ids as $id) {
            $count += $this->process((string) $id) ? 1 : 0;
        }

        return $count;
    }

    public function process(string $conversionId): bool
    {
        $conversion = $this->lease($conversionId);

        if ($conversion === null) {
            return false;
        }

        $previousTenant = $this->tenant->universityId();
        $this->tenant->setUniversityId((string) $conversion->university_id);

        try {
            $this->runSteps($conversion);

            return true;
        } catch (AdmissionEnrollmentException $e) {
            $this->fail($conversion, $e->errorCode, $e->getMessage(), permanent: true);
        } catch (Throwable $e) {
            $transient = $e instanceof QueryException && ! $e instanceof UniqueConstraintViolationException;
            $this->fail($conversion, $transient ? 'TRANSIENT_ERROR' : 'UNEXPECTED_ERROR', $e->getMessage(), permanent: ! $transient);
        } finally {
            $this->tenant->setUniversityId($previousTenant);
        }

        return false;
    }

    /** "Coba lagi" (11 §A.14): failed/manual_intervention → pending; email alternatif opsional (BR-38). */
    public function retry(StudentConversion $conversion, User $actor, ?string $emailOverride = null): StudentConversion
    {
        if ($conversion->status === ConversionStatus::Processing && $conversion->locked_until?->isFuture()) {
            throw new ConflictException('Konversi sedang diproses.', 'CONVERSION_IN_PROGRESS');
        }

        if ($conversion->status === ConversionStatus::Succeeded) {
            throw new ConflictException('Konversi sudah berhasil.', 'ALREADY_SUCCEEDED');
        }

        $from = $conversion->status;
        $conversion->forceFill([
            'status' => ConversionStatus::Pending,
            'next_attempt_at' => now(),
            'attempts' => $from === ConversionStatus::ManualIntervention ? 0 : $conversion->attempts,
            'email_override' => $emailOverride ?: $conversion->email_override,
        ])->save();
        $this->history->record($conversion->application, Lifecycle::Conversion, $from, ConversionStatus::Pending, Actor::user($actor), 'MANUAL_RETRY');

        DB::afterCommit(fn () => $this->process((string) $conversion->getKey()));

        return $conversion->refresh();
    }

    /** Lease: FOR UPDATE (+ SKIP LOCKED di MySQL), status processing, attempts+1, locked_until +5 menit. */
    private function lease(string $conversionId): ?StudentConversion
    {
        return DB::transaction(function () use ($conversionId): ?StudentConversion {
            $query = StudentConversion::query()->withoutGlobalScopes()->whereKey($conversionId);
            $query = DB::getDriverName() === 'mysql' ? $query->lock('for update skip locked') : $query->lockForUpdate();
            $conversion = $query->first();

            if ($conversion === null) {
                return null;
            }

            $leaseExpired = $conversion->status === ConversionStatus::Processing && $conversion->locked_until?->isPast();

            if ($conversion->status !== ConversionStatus::Pending && ! $leaseExpired) {
                return null;
            }

            $conversion->forceFill([
                'status' => ConversionStatus::Processing,
                'attempts' => $conversion->attempts + 1,
                'locked_until' => now()->addMinutes(self::LEASE_MINUTES),
            ])->save();

            return $conversion;
        });
    }

    private function runSteps(StudentConversion $conversion): void
    {
        $steps = (array) $conversion->steps_completed;

        if (! in_array('student_created', $steps, true)) {
            $this->createStudent($conversion);
            $steps = (array) $conversion->refresh()->steps_completed;
        }

        $student = Student::query()->withoutGlobalScopes()->findOrFail($conversion->student_id);

        foreach (['finance_posted', 'documents_linked', 'scholarship_forwarded'] as $step) {
            if (in_array($step, $steps, true)) {
                continue;
            }

            DB::transaction(function () use ($conversion, $student, $step): void {
                match ($step) {
                    'finance_posted' => $this->postFinance($conversion, $student),
                    'documents_linked' => $this->linkDocuments($conversion, $student),
                    'scholarship_forwarded' => $this->forwardScholarship($conversion, $student),
                };

                $conversion->forceFill(['steps_completed' => [...(array) $conversion->steps_completed, $step], 'locked_until' => now()->addMinutes(self::LEASE_MINUTES)])->save();
            });
        }

        DB::transaction(function () use ($conversion): void {
            $conversion->forceFill([
                'status' => ConversionStatus::Succeeded,
                'succeeded_at' => now(),
                'locked_until' => null,
                'last_error_code' => null,
                'last_error_message' => null,
            ])->save();

            $reRegistration = $conversion->reRegistration;
            $from = $reRegistration->status;

            if ($from === ReRegistrationStatus::Approved) {
                $reRegistration->forceFill(['status' => ReRegistrationStatus::Enrolled, 'enrolled_at' => now()])->save();
                $this->history->record($conversion->application, Lifecycle::ReRegistration, $from, ReRegistrationStatus::Enrolled, Actor::system());
            }

            $this->history->record($conversion->application, Lifecycle::Conversion, ConversionStatus::Processing, ConversionStatus::Succeeded, Actor::system(), metadata: ['nim' => $conversion->nim]);
            $this->outbox->record(OutboxRecorder::STUDENT_ENROLLED, $conversion, ['conversion_id' => $conversion->getKey()]);
        });
    }

    /**
     * Langkah 1: NIM (sequence ber-lock) + users + students + status history
     * dalam SATU transaksi; bentrok NIM (mis. input manual di Akademik) →
     * nomor berikutnya, maks 5 kali, lalu manual_intervention.
     */
    private function createStudent(StudentConversion $conversion): void
    {
        $existing = Student::query()->withoutGlobalScopes()->where('admission_application_id', $conversion->application_id)->first();

        if ($existing !== null) {
            $conversion->forceFill(['student_id' => $existing->getKey(), 'nim' => $existing->nim, 'user_id' => $existing->user_id, 'steps_completed' => array_values(array_unique([...(array) $conversion->steps_completed, 'student_created']))])->save();

            return;
        }

        $application = $conversion->application()->with(['account.profile', 'account.guardians', 'period.entryTerm', 'admissionPath', 'primaryEducation'])->first();
        $decision = AdmissionDecision::query()->with(['studyProgram', 'studySystem'])->where('application_id', $application->getKey())->where('is_current', true)->firstOrFail();
        $format = (string) $this->settings->get('pmb.nim_format');
        $year = $this->admissionYear($application->period?->entryTerm?->academic_year, $application->period?->starts_on);
        $values = [
            'year' => $year,
            'program' => (string) ($decision->studyProgram?->code ?? 'X'),
            'path' => (string) ($application->admissionPath?->code ?? 'X'),
            'system' => (string) ($decision->studySystem?->code ?? 'X'),
        ];

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            try {
                DB::transaction(function () use ($conversion, $application, $decision, $format, $values, $year): void {
                    $sequence = $this->sequences->next('nim', NimFormatter::sequenceKey($format, $values), (string) $application->university_id);
                    $nim = NimFormatter::render($format, $values, $sequence);
                    $student = $this->enrollment->createFromAdmission($this->enrollmentData($conversion, $application, $decision, $nim, $year));

                    $conversion->forceFill([
                        'student_id' => $student->getKey(),
                        'nim' => $nim,
                        'user_id' => $student->user_id,
                        'steps_completed' => array_values(array_unique([...(array) $conversion->steps_completed, 'student_created', 'user_linked'])),
                    ])->save();
                });

                return;
            } catch (UniqueConstraintViolationException $e) {
                if (Student::query()->withoutGlobalScopes()->where('admission_application_id', $conversion->application_id)->exists()) {
                    // Proses paralel lain sudah membuat mahasiswa (EC-14): pakai hasil yang ada.
                    $this->createStudent($conversion);

                    return;
                }

                Log::warning('[pmb-conversion] NIM bentrok, mencoba nomor berikutnya', ['conversion' => $conversion->getKey(), 'attempt' => $attempt]);
            }
        }

        throw new AdmissionEnrollmentException('NIM_COLLISION', 'NIM terus bentrok setelah 5 percobaan — periksa NIM yang diinput manual di Akademik.');
    }

    /**
     * Langkah 2: posting tagihan biaya awal ke Finance per termin (nominal
     * neto) + pembayaran sukses; idempoten by (source_type, source_id).
     */
    private function postFinance(StudentConversion $conversion, Student $student): void
    {
        $invoice = PmbInvoice::query()->where('application_id', $conversion->application_id)->where('type', InvoiceType::InitialTuition->value)
            ->whereNotIn('status', ['cancelled', 'refunded'])->with('items')->first();

        if ($invoice === null) {
            return;
        }

        $charges = $invoice->items->where('item_type', 'charge');
        $grossCents = $charges->sum(fn ($i) => Money::toCents((string) $i->amount));
        $discountCents = Money::toCents((string) $invoice->amount_discount);
        $groups = $charges->groupBy(fn ($i) => $i->installment_seq ?? 1)->sortKeys();
        $terms = [];
        $allocatedDiscount = 0;
        $index = 0;

        foreach ($groups as $seq => $items) {
            $index++;
            $termCents = $items->sum(fn ($i) => Money::toCents((string) $i->amount));
            $share = $index === $groups->count() ? $discountCents - $allocatedDiscount : ($grossCents > 0 ? (int) round($discountCents * $termCents / $grossCents) : 0);
            $allocatedDiscount += $share;
            $due = $items->max('due_at') ?? $invoice->due_at;
            $terms[] = ['seq' => (int) $seq, 'amount' => Money::fromCents(max(0, $termCents - $share)), 'due_date' => CarbonImmutable::parse($due)->toDateString()];
        }

        $payments = $invoice->transactions()->where('status', TransactionStatus::Success->value)->orderBy('paid_at')->get()
            ->map(fn ($t) => ['reference' => (string) $t->provider_reference, 'amount' => (string) $t->amount, 'paid_at' => (string) $t->paid_at, 'channel' => $t->channel])->all();

        $financeIds = $this->posting->post((string) $student->university_id, (string) $student->getKey(), (string) ($conversion->application->config_snapshot['period_code'] ?? ''), (string) $invoice->getKey(), $terms, $payments);
        $invoice->forceFill(['finance_invoice_id' => $financeIds[0] ?? null])->save();
    }

    /**
     * Langkah 3: dokumen ber-retensi `student_record` (ijazah, KK, foto, dsb.)
     * dipindahkan kepemilikannya ke record mahasiswa (FR-DOC-07).
     */
    private function linkDocuments(StudentConversion $conversion, Student $student): void
    {
        $documents = ApplicationDocument::query()->with(['documentType', 'currentVersion'])->where('application_id', $conversion->application_id)
            ->where('status', DocumentStatus::Approved->value)->get();

        foreach ($documents as $document) {
            $version = $document->currentVersion;

            if ($version === null) {
                continue;
            }

            if ($document->documentType?->code === 'PHOTO' && $student->photo_file_id === null) {
                $student->forceFill(['photo_file_id' => $version->file_upload_id])->save();
            }

            if ($document->documentType?->retention_class === 'student_record') {
                FileUpload::query()->withoutGlobalScopes()->whereKey($version->file_upload_id)
                    ->update(['fileable_type' => $student->getMorphClass(), 'fileable_id' => $student->getKey()]);
            }
        }
    }

    /** Langkah 4: beasiswa jalur masuk diteruskan ke modul Scholarship (FR-SPC-06). */
    private function forwardScholarship(StudentConversion $conversion, Student $student): void
    {
        // Model di-load agar cast `scholarship_award` (array) berlaku — ->value() mengembalikan JSON mentah.
        $award = (array) (AdmissionDecision::query()->where('application_id', $conversion->application_id)->where('is_current', true)->first()?->scholarship_award ?? []);

        if (empty($award['scholarship_id'])) {
            return;
        }

        ScholarshipApplication::query()->firstOrCreate(
            ['scholarship_id' => $award['scholarship_id'], 'student_id' => $student->getKey()],
            ['university_id' => $student->university_id, 'status' => ScholarshipApplicationStatus::Approved, 'submitted_at' => now(), 'reviewed_at' => now(), 'notes' => 'Beasiswa jalur masuk PMB ('.($award['percent'] ?? 0).'%)'],
        );
    }

    private function fail(StudentConversion $conversion, string $code, string $message, bool $permanent): void
    {
        $exhausted = $conversion->attempts >= self::MAX_ATTEMPTS;
        $status = $permanent ? ConversionStatus::Failed : ($exhausted ? ConversionStatus::ManualIntervention : ConversionStatus::Pending);

        $conversion->forceFill([
            'status' => $status,
            'locked_until' => null,
            'next_attempt_at' => $status === ConversionStatus::Pending ? now()->addMinutes(self::BACKOFF_MINUTES[min($conversion->attempts, self::MAX_ATTEMPTS) - 1] ?? 60) : null,
            'last_error_code' => $code,
            'last_error_message' => mb_substr($message, 0, 2000),
        ])->save();

        $this->history->record($conversion->application, Lifecycle::Conversion, ConversionStatus::Processing, $status, Actor::system(), $code, mb_substr($message, 0, 500));
        Log::log($status === ConversionStatus::Pending ? 'warning' : 'error', '[pmb-conversion] gagal', ['conversion' => $conversion->getKey(), 'code' => $code, 'error' => $message]);

        if ($status !== ConversionStatus::Pending) {
            $this->outbox->notifyPermission($conversion, PmbPermission::CONVERSION_RETRY, 'pmb.conversion.failed', [
                'registration_number' => (string) $conversion->application->registration_number,
                'error_code' => $code,
            ]);
        }
    }

    private function enrollmentData(StudentConversion $conversion, $application, AdmissionDecision $decision, string $nim, int $year): AdmissionEnrollmentData
    {
        $account = $application->account;
        $profile = $account->profile;
        $domain = trim((string) $this->settings->get('pmb.institution_email_domain'));
        $accountEmail = $conversion->email_override ?: ($domain !== '' ? strtolower($nim).'@'.ltrim($domain, '@') : $account->email);
        $education = $application->primaryEducation;

        return new AdmissionEnrollmentData(
            universityId: (string) $application->university_id,
            admissionApplicationId: (string) $application->getKey(),
            studyProgramId: (string) $decision->study_program_id,
            nim: $nim,
            name: (string) ($profile?->full_name ?: $account->name),
            personalEmail: $account->email,
            accountEmail: $accountEmail,
            phone: $account->phone,
            address: trim(implode(', ', array_filter([$profile?->address_line, $profile?->village, $profile?->district, $profile?->city, $profile?->province]))) ?: null,
            birthDate: $profile?->birth_date?->toDateString(),
            birthPlace: $profile?->birth_place,
            gender: $profile?->gender,
            photoFileId: null,
            admissionYear: $year,
            enrolledAt: now()->toDateString(),
            admissionPathCode: $application->admissionPath?->code,
            studySystemCode: $decision->studySystem?->code,
            academicTermId: $application->period?->entry_academic_term_id,
            profile: [
                'nik' => $profile?->nik,
                'nationality' => $profile?->nationality,
                'passport_number' => $profile?->passport_number,
                'religion' => $profile?->religion,
                'mother_name' => $profile?->mother_name,
                'father_name' => $account->guardians->firstWhere('relationship', 'father')?->full_name,
                'previous_school' => $education?->institution_name,
                'previous_school_npsn' => $education?->institution_npsn,
                'nisn' => $education?->nisn,
                'region_code' => $profile?->region_code,
                'address_line' => $profile?->address_line,
                'village' => $profile?->village,
                'district' => $profile?->district,
                'city' => $profile?->city,
                'province' => $profile?->province,
                'postal_code' => $profile?->postal_code,
                'pddikti_extra' => $profile?->pddikti_extra,
                'guardians' => $account->guardians->map(fn ($g) => ['relationship' => $g->relationship, 'full_name' => $g->full_name, 'phone' => $g->phone, 'occupation' => $g->occupation, 'education_level' => $g->education_level, 'income_range' => $g->income_range, 'is_deceased' => $g->is_deceased])->all(),
            ],
            reason: 'Mahasiswa baru PMB '.($application->period?->name ?? ''),
        );
    }

    private function admissionYear(?string $academicYear, mixed $startsOn): int
    {
        if ($academicYear && preg_match('/(\d{4})/', $academicYear, $m)) {
            return (int) $m[1];
        }

        return (int) CarbonImmutable::parse($startsOn ?? now())->format('Y');
    }
}
