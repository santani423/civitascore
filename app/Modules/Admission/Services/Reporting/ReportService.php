<?php

namespace Modules\Admission\Services\Reporting;

use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Admission\Enums\Decision;
use Modules\Admission\Enums\ExportStatus;
use Modules\Admission\Enums\FormStatus;
use Modules\Admission\Enums\InvoiceType;
use Modules\Admission\Enums\PublicationStatus;
use Modules\Admission\Enums\TransactionStatus;
use Modules\Admission\Models\AdmissionDecision;
use Modules\Admission\Models\ApplicantAccount;
use Modules\Admission\Models\Application;
use Modules\Admission\Models\ApplicationDocument;
use Modules\Admission\Models\DailyStat;
use Modules\Admission\Models\ExportLog;
use Modules\Admission\Models\PaymentTransaction;
use Modules\Admission\Models\Period;
use Modules\Admission\Models\PmbInvoice;
use Modules\Admission\Models\Quota;
use Modules\Admission\Models\Refund;
use Modules\Admission\Models\ReRegistration;
use Modules\Admission\Models\SelectionRunResult;
use Modules\Admission\Outbox\OutboxRecorder;
use Modules\Admission\Support\Money;
use Modules\Admission\Support\PmbFileStorage;
use Modules\Admission\Support\PmbPermission;
use Modules\Admission\Support\ScopeResolver;
use Modules\Admission\Support\SimpleXlsx;
use Modules\Tenancy\Models\University;

/**
 * Dashboard & pelaporan (docs/pmb/07 §M): KPI-01..19 dengan filter periode,
 * gelombang, jalur, prodi, sistem kuliah, rentang tanggal; otomatis dibatasi
 * scope role; ekspor berisi data pribadi wajib alasan & dicatat (BR-42).
 */
class ReportService
{
    /** Kode laporan → [judul, permission tambahan, berisi data pribadi]. */
    public const REPORTS = [
        'applicants' => ['Rekap Pendaftar', PmbPermission::APPLICATIONS_READ, true],
        'payments' => ['Rekap Pembayaran', PmbPermission::PAYMENTS_READ, true],
        'info-sources' => ['Sumber Informasi', null, false],
        'selection' => ['Laporan Seleksi', PmbPermission::RESULTS_READ_UNPUBLISHED, true],
        're-registration' => ['Daftar Ulang & Konversi', PmbPermission::REREG_READ, true],
        'finance' => ['Keuangan PMB', PmbPermission::PAYMENTS_READ, false],
        'quota' => ['Keterisian Kuota', null, false],
        'pddikti-completeness' => ['Kelengkapan Data PDDikti', PmbPermission::REREG_READ, false],
    ];

    public function __construct(
        private readonly ScopeResolver $scope,
        private readonly PmbFileStorage $storage,
        private readonly OutboxRecorder $outbox,
    ) {}

    /**
     * KPI ringkas (FR-RPT-01, M.2). Cache ≤ 5 menit per filter & scope.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function summary(array $filters, User $user): array
    {
        $programIds = $this->scope->programIdsFor($user, PmbPermission::REPORTS_READ);
        $key = 'pmb:kpi:'.md5(json_encode([$filters, $programIds, app(TenantContext::class)->universityId()], JSON_THROW_ON_ERROR));

        // Dinormalisasi ke array murni agar aman dibaca kembali dari Redis.
        return Cache::remember($key, 300, fn (): array => json_decode(json_encode($this->computeSummary($filters, $programIds), JSON_THROW_ON_ERROR), true));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  array<int, string>|null  $programIds
     * @return array<string, mixed>
     */
    private function computeSummary(array $filters, ?array $programIds): array
    {
        return (function () use ($filters, $programIds): array {
            $applications = $this->applicationQuery($filters, $programIds);
            $submitted = (clone $applications)->whereNotNull('first_submitted_at')->count();
            $accountsQuery = ApplicantAccount::query()->where('status', '!=', 'deactivated')
                ->when($filters['from'] ?? null, fn ($q, $v) => $q->where('created_at', '>=', CarbonImmutable::parse($v)->startOfDay()))
                ->when($filters['to'] ?? null, fn ($q, $v) => $q->where('created_at', '<=', CarbonImmutable::parse($v)->endOfDay()));
            $appIds = (clone $applications)->pluck('id');

            $invoices = PmbInvoice::query()->whereIn('application_id', $appIds)->where('type', InvoiceType::RegistrationFee->value)->get(['status']);
            $paidRegistration = $invoices->where('status.value', 'paid')->count();
            $decisions = AdmissionDecision::query()->whereIn('application_id', $appIds)->where('is_current', true)->where('publication_status', PublicationStatus::Published->value)->get(['decision']);
            $reRegistrations = ReRegistration::query()->whereIn('application_id', $appIds)->get(['status']);
            $enrolled = $reRegistrations->where('status.value', 'enrolled')->count();
            $accepted = $decisions->where('decision', Decision::Accepted)->count();
            $verified = (clone $applications)->where('verification_status', 'verified')->count();
            $accounts = $accountsQuery->count();
            $ratio = fn (int $a, int $b): ?float => $b > 0 ? round($a / $b * 100, 2) : null;

            return [
                'kpi_01_accounts' => $accounts,
                'kpi_02_verified_accounts' => (clone $accountsQuery)->where(fn ($q) => $q->whereNotNull('email_verified_at')->orWhereNotNull('phone_verified_at'))->count(),
                'kpi_03_applications' => (clone $applications)->count(),
                'kpi_04_submitted' => $submitted,
                'kpi_05_by_path' => $this->countBy(clone $applications, 'admission_path_id', true),
                'kpi_05_by_wave' => $this->countBy(clone $applications, 'wave_id', true),
                'kpi_05_by_program_first_choice' => $this->byProgram($appIds, true),
                'kpi_05_by_program_any_choice' => $this->byProgram($appIds, false),
                'kpi_06_incomplete' => (clone $applications)->whereIn('form_status', [FormStatus::Draft->value, FormStatus::RevisionRequested->value])
                    ->whereHas('wave', fn ($q) => $q->where('status', 'open'))->count(),
                'kpi_07_documents_pending' => ApplicationDocument::query()->whereIn('application_id', (clone $applications)->whereNotNull('submitted_at')->select('id'))
                    ->whereIn('status', ['uploaded', 'in_review'])->count(),
                'kpi_08_registration_payment' => [
                    'by_status' => $invoices->groupBy(fn ($i) => $i->status->value)->map->count()->all(),
                    'paid_percent' => $ratio($paidRegistration, $invoices->count()),
                ],
                'kpi_09_selection_participants' => (clone $applications)->where('selection_status', '!=', 'not_eligible')->count(),
                'kpi_10_decisions' => [
                    'accepted' => $accepted,
                    'waitlisted' => $decisions->where('decision', Decision::Waitlisted)->count(),
                    'rejected' => $decisions->where('decision', Decision::Rejected)->count(),
                    'withdrawn' => $decisions->where('decision', Decision::Withdrawn)->count(),
                ],
                'kpi_11_re_registration' => $reRegistrations->groupBy(fn ($r) => $r->status->value)->map->count()->all(),
                'kpi_12_conversion' => [
                    'total' => $ratio($enrolled, $submitted),
                    'paid_per_account' => $ratio($paidRegistration, $accounts),
                    'accepted_per_verified' => $ratio($accepted, $verified),
                    'yield' => $ratio($enrolled, $accepted),
                ],
                'kpi_13_quota' => $this->quotaAttainment($filters, $programIds),
                'kpi_14_revenue' => $this->revenue($appIds),
                'kpi_17_info_sources' => $this->infoSources($applications),
                'kpi_18_verification_sla' => $this->verificationSla($appIds),
                'kpi_19_pddikti_completeness' => $this->pddiktiCompleteness($appIds),
                'funnel' => [
                    ['stage' => 'accounts', 'value' => $accounts],
                    ['stage' => 'submitted', 'value' => $submitted],
                    ['stage' => 'paid', 'value' => $paidRegistration],
                    ['stage' => 'verified', 'value' => $verified],
                    ['stage' => 'accepted', 'value' => $accepted],
                    ['stage' => 'enrolled', 'value' => $enrolled],
                ],
                'generated_at' => now()->toIso8601String(),
            ];
        })();
    }

    /**
     * KPI-15: seri harian (zona waktu tenant).
     *
     * @param  array<string, mixed>  $filters
     * @param  array<int, string>  $metrics
     * @return array<string, array<int, array{date: string, value: int|float}>>
     */
    public function trend(array $filters, array $metrics, string $from, string $to, User $user): array
    {
        $programIds = $this->scope->programIdsFor($user, PmbPermission::REPORTS_READ);
        $timezone = $this->timezone();
        $start = CarbonImmutable::parse($from, $timezone)->startOfDay();
        $end = CarbonImmutable::parse($to, $timezone)->endOfDay();

        if ($start->diffInDays($end) > 366) {
            throw ValidationException::withMessages(['to' => 'Rentang maksimal 366 hari.']);
        }

        $appIds = $this->applicationQuery($filters, $programIds)->pluck('id');
        $series = [];
        $sources = [
            'accounts' => fn () => ApplicantAccount::query()->whereBetween('created_at', [$start, $end])->pluck('created_at'),
            'submitted' => fn () => Application::query()->whereIn('id', $appIds)->whereBetween('first_submitted_at', [$start, $end])->pluck('first_submitted_at'),
            'paid' => fn () => PmbInvoice::query()->whereIn('application_id', $appIds)->where('type', InvoiceType::RegistrationFee->value)->whereBetween('paid_at', [$start, $end])->pluck('paid_at'),
            'enrolled' => fn () => ReRegistration::query()->whereIn('application_id', $appIds)->whereBetween('enrolled_at', [$start, $end])->pluck('enrolled_at'),
        ];

        foreach ($metrics as $metric) {
            if (! isset($sources[$metric])) {
                continue;
            }

            $counts = $sources[$metric]()->groupBy(fn ($value) => CarbonImmutable::parse($value)->setTimezone($timezone)->toDateString())->map->count();
            $points = [];

            for ($day = $start; $day->lte($end); $day = $day->addDay()) {
                $points[] = ['date' => $day->toDateString(), 'value' => (int) ($counts[$day->toDateString()] ?? 0)];
            }

            $series[$metric] = $points;
        }

        return $series;
    }

    /**
     * KPI-16: perbandingan antarperiode pada hari relatif terhadap pembukaan
     * gelombang pertama (H+n), dari snapshot harian `pmb_daily_stats`.
     *
     * @param  array<int, string>  $periodIds
     * @return array<string, array<int, array{day: int, value: float}>>
     */
    public function comparison(array $periodIds, string $metric, int $maxDay): array
    {
        $result = [];

        foreach (Period::query()->whereIn('id', $periodIds)->with('waves')->get() as $period) {
            $opens = $period->waves->min('registration_opens_at');

            if ($opens === null) {
                continue;
            }

            $origin = CarbonImmutable::parse($opens)->setTimezone($this->timezone())->startOfDay();
            $stats = DailyStat::query()->where('period_id', $period->getKey())->where('metric', $metric)
                ->where('wave_id', '')->where('admission_path_id', '')->where('study_program_id', '')
                ->orderBy('stat_date')->get();

            $result[$period->code] = $stats->map(fn (DailyStat $s) => ['day' => (int) $origin->diffInDays(CarbonImmutable::parse($s->stat_date), false), 'value' => (float) $s->value])
                ->filter(fn ($p) => $p['day'] >= 0 && $p['day'] <= $maxDay)->values()->all();
        }

        return $result;
    }

    /**
     * Snapshot harian (job 00:30, M.2 agregasi) — kumulatif per periode.
     */
    public function snapshotDaily(?CarbonImmutable $date = null): int
    {
        $date ??= CarbonImmutable::now($this->timezone())->subDay();
        $end = $date->endOfDay();
        $count = 0;

        foreach (Period::query()->whereIn('status', ['published', 'closed'])->get() as $period) {
            $apps = Application::query()->where('period_id', $period->getKey());
            $appIds = (clone $apps)->pluck('id');
            $metrics = [
                'accounts' => Application::query()->where('period_id', $period->getKey())->where('created_at', '<=', $end)->distinct()->count('applicant_account_id'),
                'applications' => (clone $apps)->where('created_at', '<=', $end)->count(),
                'submitted' => (clone $apps)->where('first_submitted_at', '<=', $end)->count(),
                'paid' => PmbInvoice::query()->whereIn('application_id', $appIds)->where('type', 'registration_fee')->where('paid_at', '<=', $end)->count(),
                'verified' => (clone $apps)->where('verified_at', '<=', $end)->count(),
                'accepted' => AdmissionDecision::query()->whereIn('application_id', $appIds)->where('decision', 'accepted')->where('published_at', '<=', $end)->distinct()->count('application_id'),
                'enrolled' => ReRegistration::query()->whereIn('application_id', $appIds)->where('enrolled_at', '<=', $end)->count(),
                'revenue' => (float) Money::fromCents(PaymentTransaction::query()->where('status', TransactionStatus::Success->value)->where('paid_at', '<=', $end)
                    ->whereHas('invoice', fn ($q) => $q->whereIn('application_id', $appIds))->get()->sum(fn ($t) => Money::toCents((string) $t->amount))),
            ];

            foreach ($metrics as $metric => $value) {
                DailyStat::query()->updateOrCreate(
                    ['stat_date' => $date->toDateString(), 'period_id' => $period->getKey(), 'wave_id' => '', 'admission_path_id' => '', 'study_program_id' => '', 'metric' => $metric],
                    ['value' => $value],
                );
                $count++;
            }
        }

        return $count;
    }

    /**
     * Laporan tabel (11 §A.15 `reports/{code}`).
     *
     * @param  array<string, mixed>  $filters
     * @return array{title: string, columns: array<int, array{key: string, label: string}>, rows: Collection<int, array<string, mixed>>}
     */
    public function report(string $code, array $filters, User $user): array
    {
        [$title, $permission] = self::REPORTS[$code] ?? throw ValidationException::withMessages(['code' => 'Laporan tidak dikenal.']);

        if ($permission !== null && ! $user->hasPermissionTo($permission)) {
            abort(403, 'Anda tidak memiliki izin untuk laporan ini.');
        }

        $programIds = $this->scope->programIdsFor($user, PmbPermission::REPORTS_READ);
        $personal = $user->hasPermissionTo(PmbPermission::APPLICATIONS_READ);
        $applications = $this->applicationQuery($filters, $programIds);

        [$columns, $rows] = match ($code) {
            'applicants' => $this->applicantRows($applications, $personal),
            'payments' => $this->paymentRows($applications, $filters, $personal),
            'info-sources' => [[['key' => 'info_source', 'label' => 'Sumber informasi'], ['key' => 'submitted', 'label' => 'Pendaftar'], ['key' => 'enrolled', 'label' => 'Menjadi mahasiswa'], ['key' => 'conversion', 'label' => 'Konversi (%)']], collect($this->infoSources($applications))],
            'selection' => $this->selectionRows($applications, $personal),
            're-registration' => $this->reRegistrationRows($applications, $personal),
            'finance' => $this->financeRows($applications),
            'quota' => [[['key' => 'program', 'label' => 'Prodi'], ['key' => 'path', 'label' => 'Jalur'], ['key' => 'system', 'label' => 'Sistem'], ['key' => 'capacity', 'label' => 'Kapasitas'], ['key' => 'accepted', 'label' => 'Diterima aktif'], ['key' => 'enrolled', 'label' => 'Enrolled'], ['key' => 'attainment', 'label' => 'Capaian (%)']], collect($this->quotaAttainment($filters, $programIds))],
            'pddikti-completeness' => $this->pddiktiRows($applications),
        };

        return ['title' => $title, 'columns' => $columns, 'rows' => $rows];
    }

    /**
     * Ekspor (FR-RPT-08, M.4): alasan wajib, kolom dibatasi hak, dicatat di
     * pmb_export_logs; diproses asinkron, tautan berlaku 24 jam.
     *
     * @param  array<string, mixed>  $filters
     * @param  array<int, string>  $columns
     */
    public function requestExport(string $code, string $format, array $filters, array $columns, string $reason, User $user): ExportLog
    {
        if (! in_array($format, ['xlsx', 'csv', 'pdf'], true)) {
            throw ValidationException::withMessages(['format' => 'Format harus xlsx, csv, atau pdf.']);
        }

        if (mb_strlen(trim($reason)) < 5) {
            throw ValidationException::withMessages(['reason' => 'Alasan ekspor wajib diisi (BR-42).']);
        }

        // Validasi hak akses sebelum antre.
        $this->report($code, $filters, $user);

        $log = ExportLog::query()->create([
            'user_id' => $user->getKey(),
            'report_code' => $code,
            'format' => $format,
            'filters' => $filters,
            'columns' => $columns,
            'reason' => $reason,
            'contains_personal_data' => (self::REPORTS[$code][2] ?? false) && $user->hasPermissionTo(PmbPermission::APPLICATIONS_READ),
            'status' => ExportStatus::Pending,
        ]);

        $this->outbox->record(OutboxRecorder::EXPORT, $log, ['export_id' => $log->getKey(), 'user_id' => $user->getKey()]);

        return $log->refresh();
    }

    public function generateExport(ExportLog $log): void
    {
        $user = User::query()->findOrFail($log->user_id);
        $log->forceFill(['status' => ExportStatus::Processing])->save();
        $report = $this->report($log->report_code, (array) $log->filters, $user);
        $columns = collect($report['columns'])->when(! empty($log->columns), fn ($c) => $c->whereIn('key', (array) $log->columns))->values();
        $headers = $columns->pluck('label')->all();
        $rows = $report['rows']->map(fn ($row) => $columns->map(fn ($c) => is_scalar($row[$c['key']] ?? null) || ($row[$c['key']] ?? null) === null ? ($row[$c['key']] ?? '') : json_encode($row[$c['key']]))->all());
        $name = 'pmb-'.$log->report_code.'-'.now()->format('Ymd-His');
        $tmp = tempnam(sys_get_temp_dir(), 'pmbexp');

        if ($log->format === 'xlsx') {
            SimpleXlsx::write($tmp, $headers, $rows->all(), mb_substr($report['title'], 0, 31));
            $mime = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        } elseif ($log->format === 'csv') {
            $handle = fopen($tmp, 'wb');

            if ($handle === false) {
                throw new \RuntimeException('Berkas sementara ekspor tidak dapat dibuat.');
            }

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $headers);

            foreach ($rows as $row) {
                fputcsv($handle, array_map(fn ($v) => is_string($v) && preg_match('/^[=+\-@]/', $v) ? "'".$v : $v, $row));
            }

            fclose($handle);
            $mime = 'text/csv';
        } else {
            $html = view('admission::pdf.report', ['title' => $report['title'], 'headers' => $headers, 'rows' => $rows, 'user' => $user, 'filters' => $log->filters])->render();
            file_put_contents($tmp, Pdf::loadHTML($html)->setPaper('a4', 'landscape')->output());
            $mime = 'application/pdf';
        }

        $file = $this->storage->storeGenerated((string) file_get_contents($tmp), $mime, $log->format, $name.'.'.$log->format, ['exports', (string) $log->user_id]);
        @unlink($tmp);
        $log->forceFill(['status' => ExportStatus::Ready, 'file_upload_id' => $file->getKey(), 'row_count' => $rows->count(), 'expires_at' => now()->addDay()])->save();
    }

    // ------------------------------------------------------------------ Query dasar

    /**
     * @param  array<string, mixed>  $filters
     * @param  array<int, string>|null  $programIds
     * @return Builder<Application>
     */
    public function applicationQuery(array $filters, ?array $programIds): Builder
    {
        $query = Application::query()->where('form_status', '!=', FormStatus::Void->value)
            ->when($filters['period_id'] ?? null, fn ($q, $v) => $q->where('period_id', $v))
            ->when($filters['wave_id'] ?? null, fn ($q, $v) => $q->where('wave_id', $v))
            ->when($filters['admission_path_id'] ?? null, fn ($q, $v) => $q->where('admission_path_id', $v))
            ->when($filters['from'] ?? null, fn ($q, $v) => $q->where('created_at', '>=', CarbonImmutable::parse($v)->startOfDay()))
            ->when($filters['to'] ?? null, fn ($q, $v) => $q->where('created_at', '<=', CarbonImmutable::parse($v)->endOfDay()));

        $programFilter = $filters['study_program_id'] ?? null;
        $systemFilter = $filters['study_system_id'] ?? null;
        $faculty = $filters['faculty_id'] ?? null;

        if ($programFilter || $systemFilter || $faculty) {
            $firstOnly = ($filters['choice'] ?? 'first') === 'first';
            $query->whereIn('id', function ($sub) use ($programFilter, $systemFilter, $faculty, $firstOnly): void {
                $sub->select('application_id')->from('pmb_application_choices')
                    ->when($firstOnly, fn ($q) => $q->where('priority', 1))
                    ->when($programFilter, fn ($q, $v) => $q->where('study_program_id', $v))
                    ->when($systemFilter, fn ($q, $v) => $q->where('study_system_id', $v))
                    ->when($faculty, fn ($q, $v) => $q->whereIn('study_program_id', DB::table('study_programs')->where('faculty_id', $v)->select('id')));
            });
        }

        if ($programIds !== null) {
            $query->whereIn('id', fn ($sub) => $sub->select('application_id')->from('pmb_application_choices')->whereIn('study_program_id', $programIds === [] ? ['-'] : $programIds));
        }

        return $query;
    }

    // ------------------------------------------------------------------ Helper KPI

    /** @return array<string, int> */
    private function countBy(Builder $query, string $column, bool $submittedOnly): array
    {
        return $query->when($submittedOnly, fn ($q) => $q->whereNotNull('first_submitted_at'))
            ->select($column, DB::raw('COUNT(*) as total'))->groupBy($column)->pluck('total', $column)->map(fn ($v) => (int) $v)->all();
    }

    /**
     * @param  Collection<int, string>  $appIds
     * @return array<string, int>
     */
    private function byProgram(Collection $appIds, bool $firstChoiceOnly): array
    {
        return DB::table('pmb_application_choices')->join('pmb_applications', 'pmb_applications.id', '=', 'pmb_application_choices.application_id')
            ->whereIn('pmb_application_choices.application_id', $appIds)->whereNotNull('pmb_applications.first_submitted_at')
            ->when($firstChoiceOnly, fn ($q) => $q->where('priority', 1))
            ->select('pmb_application_choices.study_program_id', DB::raw('COUNT(DISTINCT pmb_application_choices.application_id) as total'))
            ->groupBy('pmb_application_choices.study_program_id')->pluck('total', 'study_program_id')->map(fn ($v) => (int) $v)->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  array<int, string>|null  $programIds
     * @return array<int, array<string, mixed>>
     */
    private function quotaAttainment(array $filters, ?array $programIds): array
    {
        if (empty($filters['period_id'])) {
            return [];
        }

        return Quota::query()->with(['studyProgram', 'studySystem', 'admissionPath'])->where('period_id', $filters['period_id'])
            ->when($filters['admission_path_id'] ?? null, fn ($q, $v) => $q->where('admission_path_id', $v))
            ->when($programIds !== null, fn ($q) => $q->whereIn('study_program_id', $programIds === [] ? ['-'] : $programIds))
            ->get()->map(function (Quota $quota): array {
                $active = ReRegistration::query()->whereIn('status', ['awaiting_confirmation', 'confirmed', 'in_progress', 'submitted', 'revision_requested', 'approved'])
                    ->whereHas('decision', fn ($q) => $q->where('quota_id', $quota->getKey())->where('is_current', true))->count();
                $enrolled = ReRegistration::query()->where('status', 'enrolled')->whereHas('decision', fn ($q) => $q->where('quota_id', $quota->getKey()))->count();

                return [
                    'quota_id' => $quota->getKey(),
                    'program' => $quota->studyProgram?->name,
                    'path' => $quota->admissionPath?->name,
                    'system' => $quota->studySystem?->name,
                    'capacity' => $quota->capacity,
                    'accepted' => $quota->accepted_count,
                    'enrolled' => $enrolled,
                    'attainment' => $quota->capacity > 0 ? round(($enrolled + $active) / $quota->capacity * 100, 2) : null,
                    'accepted_ratio' => $quota->capacity > 0 ? round($quota->accepted_count / $quota->capacity * 100, 2) : null,
                ];
            })->all();
    }

    /**
     * KPI-14: Σ transaksi sukses − refund selesai per jenis/kanal; piutang biaya awal.
     *
     * @param  Collection<int, string>  $appIds
     * @return array<string, mixed>
     */
    private function revenue(Collection $appIds): array
    {
        $transactions = PaymentTransaction::query()->with('invoice:id,type')->where('status', TransactionStatus::Success->value)
            ->whereHas('invoice', fn ($q) => $q->whereIn('application_id', $appIds))->get(['id', 'invoice_id', 'amount', 'channel']);
        $refunded = Refund::query()->where('status', 'completed')->whereIn('transaction_id', $transactions->pluck('id'))->get()->sum(fn ($r) => Money::toCents((string) $r->amount));
        $receivable = PmbInvoice::query()->whereIn('application_id', $appIds)->where('type', InvoiceType::InitialTuition->value)
            ->whereIn('status', ['unpaid', 'pending', 'partially_paid'])->get()->sum(fn ($i) => Money::toCents($i->outstanding()));

        return [
            'total' => Money::fromCents($transactions->sum(fn ($t) => Money::toCents((string) $t->amount)) - $refunded),
            'by_type' => $transactions->groupBy(fn ($t) => $t->invoice?->type?->value)->map(fn ($g) => Money::fromCents($g->sum(fn ($t) => Money::toCents((string) $t->amount))))->all(),
            'by_channel' => $transactions->groupBy('channel')->map(fn ($g) => Money::fromCents($g->sum(fn ($t) => Money::toCents((string) $t->amount))))->all(),
            'refunded' => Money::fromCents($refunded),
            'initial_tuition_receivable' => Money::fromCents($receivable),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function infoSources(Builder $applications): array
    {
        $rows = (clone $applications)->whereNotNull('first_submitted_at')->with('reRegistration:id,application_id,status')->get(['id', 'info_source']);

        return $rows->groupBy(fn ($a) => $a->info_source ?? 'unknown')->map(function ($group, $source) {
            $enrolled = $group->filter(fn ($a) => $a->reRegistration?->status?->value === 'enrolled')->count();

            return ['info_source' => $source, 'submitted' => $group->count(), 'enrolled' => $enrolled, 'conversion' => $group->count() ? round($enrolled / $group->count() * 100, 2) : 0];
        })->values()->all();
    }

    /**
     * KPI-18: median & P90 (verified_at − max(submitted_at, paid_at)) dalam jam.
     *
     * @param  Collection<int, string>  $appIds
     * @return array{median_hours: ?float, p90_hours: ?float, sample: int}
     */
    private function verificationSla(Collection $appIds): array
    {
        $durations = Application::query()->whereIn('id', $appIds)->whereNotNull('verified_at')->with(['invoices' => fn ($q) => $q->where('type', 'registration_fee')])
            ->get(['id', 'first_submitted_at', 'verified_at'])
            ->map(function (Application $a) {
                $start = collect([$a->first_submitted_at, $a->invoices->first()?->paid_at])->filter()->max();

                return $start ? max(0, $a->verified_at->diffInMinutes($start, true) / 60) : null;
            })->filter()->sort()->values();

        $percentile = fn (float $p) => $durations->isEmpty() ? null : round((float) $durations[(int) floor(($durations->count() - 1) * $p)], 2);

        return ['median_hours' => $percentile(0.5), 'p90_hours' => $percentile(0.9), 'sample' => $durations->count()];
    }

    /**
     * @param  Collection<int, string>  $appIds
     * @return array{percent: ?float, enrolled: int, complete: int}
     */
    private function pddiktiCompleteness(Collection $appIds): array
    {
        $enrolledApps = ReRegistration::query()->whereIn('application_id', $appIds)->where('status', 'enrolled')->pluck('id');
        $complete = DB::table('pmb_re_registration_checks')->whereIn('re_registration_id', $enrolledApps)->where('check_code', 'PDDIKTI_DATA_COMPLETE')->where('status', 'passed')->count();

        return ['percent' => $enrolledApps->count() ? round($complete / $enrolledApps->count() * 100, 2) : null, 'enrolled' => $enrolledApps->count(), 'complete' => $complete];
    }

    // ------------------------------------------------------------------ Baris laporan

    /** @return array{0: array<int, array{key: string, label: string}>, 1: Collection<int, array<string, mixed>>} */
    private function applicantRows(Builder $applications, bool $personal): array
    {
        $columns = [
            ['key' => 'registration_number', 'label' => 'No. Pendaftaran'],
            ...($personal ? [['key' => 'name', 'label' => 'Nama'], ['key' => 'email', 'label' => 'Email'], ['key' => 'phone', 'label' => 'HP']] : []),
            ['key' => 'path', 'label' => 'Jalur'], ['key' => 'wave', 'label' => 'Gelombang'], ['key' => 'first_choice', 'label' => 'Pilihan 1'],
            ['key' => 'school', 'label' => 'Asal sekolah'], ['key' => 'city', 'label' => 'Kota'], ['key' => 'province', 'label' => 'Provinsi'],
            ['key' => 'form_status', 'label' => 'Status formulir'], ['key' => 'payment_status', 'label' => 'Pembayaran'], ['key' => 'verification_status', 'label' => 'Verifikasi'],
            ['key' => 'selection_status', 'label' => 'Seleksi'], ['key' => 'info_source', 'label' => 'Sumber informasi'], ['key' => 'submitted_at', 'label' => 'Dikirim'],
        ];

        $rows = (clone $applications)->with(['account.profile', 'admissionPath', 'wave', 'choices.studyProgram', 'primaryEducation'])->orderBy('registration_number')->limit(50000)->get()
            ->map(fn (Application $a) => [
                'registration_number' => $a->registration_number,
                'name' => $a->account->profile?->full_name ?? $a->account->name,
                'email' => $a->account->email,
                'phone' => $a->account->phone,
                'path' => $a->admissionPath?->name,
                'wave' => $a->wave?->name,
                'first_choice' => $a->choices->first()?->studyProgram?->name,
                'school' => $a->primaryEducation?->institution_name,
                'city' => $a->account->profile?->city,
                'province' => $a->account->profile?->province,
                'form_status' => $a->form_status->value,
                'payment_status' => $a->registration_payment_status->value,
                'verification_status' => $a->verification_status->value,
                'selection_status' => $a->selection_status->value,
                'info_source' => $a->info_source,
                'submitted_at' => $a->first_submitted_at?->toDateTimeString(),
            ]);

        return [$columns, $rows];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{0: array<int, array{key: string, label: string}>, 1: Collection<int, array<string, mixed>>}
     */
    private function paymentRows(Builder $applications, array $filters, bool $personal): array
    {
        $columns = [
            ['key' => 'invoice_number', 'label' => 'No. Tagihan'], ['key' => 'registration_number', 'label' => 'No. Pendaftaran'],
            ...($personal ? [['key' => 'name', 'label' => 'Nama']] : []),
            ['key' => 'type', 'label' => 'Jenis'], ['key' => 'channel', 'label' => 'Kanal'], ['key' => 'amount', 'label' => 'Nominal'],
            ['key' => 'status', 'label' => 'Status'], ['key' => 'paid_at', 'label' => 'Dibayar'], ['key' => 'receipt_number', 'label' => 'Kuitansi'],
        ];

        $rows = PaymentTransaction::query()->with(['invoice.application.account.profile'])
            ->whereHas('invoice', fn ($q) => $q->whereIn('application_id', (clone $applications)->select('id')))
            ->when($filters['payment_status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->orderByDesc('created_at')->limit(50000)->get()
            ->map(fn (PaymentTransaction $t) => [
                'invoice_number' => $t->invoice?->invoice_number,
                'registration_number' => $t->invoice?->application?->registration_number,
                'name' => $t->invoice?->application?->account?->name,
                'type' => $t->invoice?->type?->value,
                'channel' => $t->channel,
                'amount' => (string) $t->amount,
                'status' => $t->status->value,
                'paid_at' => $t->paid_at?->toDateTimeString(),
                'receipt_number' => $t->receipt_number,
            ]);

        return [$columns, $rows];
    }

    /** @return array{0: array<int, array{key: string, label: string}>, 1: Collection<int, array<string, mixed>>} */
    private function selectionRows(Builder $applications, bool $personal): array
    {
        $columns = [
            ['key' => 'registration_number', 'label' => 'No. Pendaftaran'], ...($personal ? [['key' => 'name', 'label' => 'Nama']] : []),
            ['key' => 'final_score', 'label' => 'Skor akhir'], ['key' => 'rank', 'label' => 'Peringkat'], ['key' => 'recommendation', 'label' => 'Rekomendasi'],
            ['key' => 'program', 'label' => 'Prodi ditempatkan'], ['key' => 'decision', 'label' => 'Keputusan'], ['key' => 'published', 'label' => 'Terbit'],
        ];

        $rows = SelectionRunResult::query()->with(['application.account', 'assignedQuota.studyProgram', 'run'])
            ->whereIn('application_id', (clone $applications)->select('id'))
            ->whereHas('run', fn ($q) => $q->whereIn('status', ['computed', 'under_review', 'pending_approval', 'approved', 'published']))
            ->orderBy('rank_global')->limit(50000)->get()
            ->map(function (SelectionRunResult $r) {
                $decision = AdmissionDecision::query()->where('application_id', $r->application_id)->where('is_current', true)->first();

                return [
                    'registration_number' => $r->application?->registration_number,
                    'name' => $r->application?->account?->name,
                    'final_score' => $r->final_score_rounded,
                    'rank' => $r->rank_global,
                    'recommendation' => $r->effectiveRecommendation(),
                    'program' => $r->assignedQuota?->studyProgram?->name,
                    'decision' => $decision?->decision?->value,
                    'published' => $decision?->publication_status === PublicationStatus::Published ? 'ya' : 'belum',
                ];
            });

        return [$columns, $rows];
    }

    /** @return array{0: array<int, array{key: string, label: string}>, 1: Collection<int, array<string, mixed>>} */
    private function reRegistrationRows(Builder $applications, bool $personal): array
    {
        $columns = [
            ['key' => 'registration_number', 'label' => 'No. Pendaftaran'], ...($personal ? [['key' => 'name', 'label' => 'Nama']] : []),
            ['key' => 'program', 'label' => 'Prodi'], ['key' => 'status', 'label' => 'Status daftar ulang'], ['key' => 'deadline', 'label' => 'Batas'],
            ['key' => 'conversion_status', 'label' => 'Konversi'], ['key' => 'nim', 'label' => 'NIM'],
        ];

        $rows = ReRegistration::query()->with(['application.account', 'decision.studyProgram', 'application.conversion'])
            ->whereIn('application_id', (clone $applications)->select('id'))->limit(50000)->get()
            ->map(fn (ReRegistration $r) => [
                'registration_number' => $r->application?->registration_number,
                'name' => $r->application?->account?->name,
                'program' => $r->decision?->studyProgram?->name,
                'status' => $r->status->value,
                'deadline' => $r->effectiveDeadline()->toDateTimeString(),
                'conversion_status' => $r->application?->conversion?->status?->value,
                'nim' => $r->application?->conversion?->nim,
            ]);

        return [$columns, $rows];
    }

    /** @return array{0: array<int, array{key: string, label: string}>, 1: Collection<int, array<string, mixed>>} */
    private function financeRows(Builder $applications): array
    {
        $columns = [['key' => 'metric', 'label' => 'Indikator'], ['key' => 'value', 'label' => 'Nilai']];
        $revenue = $this->revenue((clone $applications)->pluck('id'));
        $rows = collect([
            ['metric' => 'Total penerimaan bersih', 'value' => $revenue['total']],
            ['metric' => 'Refund selesai', 'value' => $revenue['refunded']],
            ['metric' => 'Piutang biaya awal', 'value' => $revenue['initial_tuition_receivable']],
            ...collect($revenue['by_type'])->map(fn ($v, $k) => ['metric' => 'Penerimaan '.$k, 'value' => $v])->values()->all(),
            ...collect($revenue['by_channel'])->map(fn ($v, $k) => ['metric' => 'Kanal '.$k, 'value' => $v])->values()->all(),
            ['metric' => 'Item rekonsiliasi terbuka', 'value' => (string) DB::table('pmb_reconciliation_items')->whereNull('resolution')->where('result', '!=', 'matched')->count()],
            ['metric' => 'Tagihan perlu resolusi (BR-18)', 'value' => (string) PmbInvoice::query()->where('needs_resolution', true)->count()],
        ]);

        return [$columns, $rows];
    }

    /** @return array{0: array<int, array{key: string, label: string}>, 1: Collection<int, array<string, mixed>>} */
    private function pddiktiRows(Builder $applications): array
    {
        $columns = [['key' => 'program', 'label' => 'Prodi'], ['key' => 'enrolled', 'label' => 'Enrolled'], ['key' => 'complete', 'label' => 'Data lengkap'], ['key' => 'percent', 'label' => 'Kelengkapan (%)']];
        $rows = ReRegistration::query()->with(['decision.studyProgram', 'checks'])->where('status', 'enrolled')->whereIn('application_id', (clone $applications)->select('id'))->get()
            ->groupBy(fn ($r) => $r->decision?->studyProgram?->name ?? '-')
            ->map(function ($group, $program) {
                $complete = $group->filter(fn ($r) => $r->checks->firstWhere('check_code', 'PDDIKTI_DATA_COMPLETE')?->status?->value === 'passed')->count();

                return ['program' => $program, 'enrolled' => $group->count(), 'complete' => $complete, 'percent' => round($complete / max(1, $group->count()) * 100, 2)];
            })->values();

        return [$columns, $rows];
    }

    private function timezone(): string
    {
        return (string) (University::query()->whereKey(app(TenantContext::class)->universityId())->value('timezone') ?: config('app.timezone'));
    }
}
