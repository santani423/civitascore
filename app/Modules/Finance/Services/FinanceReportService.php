<?php

namespace Modules\Finance\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Finance\Enums\BillGroup;
use Modules\Finance\Enums\InvoiceStatus;
use Modules\Finance\Enums\PaymentMethod;
use Modules\Finance\Enums\TransactionStatus;
use Modules\Finance\Support\Money;

/**
 * Laporan KEU (rancangan KEU §11.3). Setiap laporan = kolom + baris +
 * ringkasan dengan definisi angka §11.1, sehingga konsisten dengan
 * dashboard. Tampilan dibatasi `limit` baris; ekspor CSV memuat semua baris.
 */
class FinanceReportService
{
    public const TYPES = [
        'invoices' => 'Laporan Tagihan',
        'payments' => 'Laporan Penerimaan (Pembayaran)',
        'receivables' => 'Laporan Piutang per Mahasiswa',
        'arrears' => 'Laporan Tunggakan',
        'aging' => 'Umur Piutang (Aging)',
        'aid' => 'Laporan Beasiswa & Potongan',
        'penalties' => 'Laporan Denda',
        'deposits' => 'Laporan Saldo Deposit',
        'installments' => 'Laporan Cicilan',
        'transactions' => 'Laporan Transaksi (Manual, Tunai, Online)',
        'reconciliation' => 'Laporan Rekonsiliasi',
        'refunds' => 'Laporan Pengembalian Dana',
        'officer_activity' => 'Laporan Aktivitas Petugas',
    ];

    public function __construct(private readonly FinanceQuery $query, private readonly FinanceDashboardService $dashboard) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array{type: string, title: string, columns: array<int, array{key: string, label: string}>, rows: array<int, array<string, mixed>>, summary: array<string, mixed>, total_rows: int}
     */
    public function build(string $type, array $filters, ?int $limit = 500): array
    {
        if (! isset(self::TYPES[$type])) {
            throw new InvalidArgumentException('Jenis laporan tidak dikenal.');
        }

        [$columns, $rows, $summary] = $this->{'report'.str_replace('_', '', ucwords($type, '_'))}($filters);
        $total = count($rows);

        return [
            'type' => $type,
            'title' => self::TYPES[$type],
            'columns' => array_map(fn ($k, $l) => ['key' => $k, 'label' => $l], array_keys($columns), $columns),
            'rows' => $limit === null ? $rows : array_slice($rows, 0, $limit),
            'summary' => $summary,
            'total_rows' => $total,
        ];
    }

    /** @param  array<string, mixed>  $filters */
    private function reportInvoices(array $filters): array
    {
        $rows = $this->query->invoices($filters)->orderBy('invoices.due_date')->get([
            'invoices.invoice_number', 'students.nim', 'students.name', 'study_programs.name as program', 'students.admission_year', 'invoices.period',
            'invoices.bill_group', 'invoices.gross_amount', 'invoices.discount_amount', 'invoices.adjustment_amount', 'invoices.amount', 'invoices.paid_amount',
            'invoices.status', 'invoices.due_date', 'invoices.is_overdue',
        ])->map(fn ($r) => [
            'invoice_number' => $r->invoice_number, 'nim' => $r->nim, 'name' => $r->name, 'program' => $r->program, 'admission_year' => $r->admission_year,
            'period' => $r->period, 'bill_group' => BillGroup::tryFrom((string) $r->bill_group)?->label() ?? $r->bill_group,
            'gross' => Money::normalize((string) $r->gross_amount), 'discount' => Money::normalize((string) $r->discount_amount),
            'adjustment' => Money::normalize((string) $r->adjustment_amount), 'net' => Money::normalize((string) $r->amount),
            'paid' => Money::normalize((string) $r->paid_amount),
            'outstanding' => in_array($r->status, ['unpaid', 'partial'], true) ? Money::sub((string) $r->amount, (string) $r->paid_amount) : '0.00',
            'status' => (InvoiceStatus::tryFrom((string) $r->status)?->label() ?? $r->status).($r->is_overdue ? ' (terlambat)' : ''),
            'due_date' => $r->due_date,
        ])->all();

        return [[
            'invoice_number' => 'No. Tagihan', 'nim' => 'NIM', 'name' => 'Nama', 'program' => 'Prodi', 'admission_year' => 'Angkatan', 'period' => 'Periode',
            'bill_group' => 'Kelompok', 'gross' => 'Bruto', 'discount' => 'Potongan', 'adjustment' => 'Penyesuaian', 'net' => 'Neto', 'paid' => 'Dibayar',
            'outstanding' => 'Sisa', 'status' => 'Status', 'due_date' => 'Jatuh Tempo',
        ], $rows, $this->sums($rows, ['gross', 'discount', 'adjustment', 'net', 'paid', 'outstanding']) + ['count' => count($rows)]];
    }

    /** @param  array<string, mixed>  $filters */
    private function reportPayments(array $filters): array
    {
        $rows = $this->query->transactions($filters)->where('payment_transactions.status', TransactionStatus::Success->value)
            ->where('payment_transactions.method', '!=', PaymentMethod::Deposit->value)
            ->orderBy('payment_transactions.paid_at')->get([
                'payment_transactions.paid_at', 'payment_transactions.transaction_number', 'payment_transactions.receipt_number', 'students.nim', 'students.name',
                'payment_transactions.method', 'payment_channels.name as channel', 'payment_transactions.amount', 'payment_transactions.channel_fee', 'payment_transactions.reconciliation_status',
            ])->map(fn ($r) => [
                'paid_at' => (string) $r->paid_at, 'transaction_number' => $r->transaction_number, 'receipt_number' => $r->receipt_number, 'nim' => $r->nim, 'name' => $r->name,
                'method' => PaymentMethod::tryFrom((string) $r->method)?->label() ?? $r->method, 'channel' => $r->channel ?? '—',
                'amount' => Money::normalize((string) $r->amount), 'channel_fee' => Money::normalize((string) $r->channel_fee), 'reconciliation' => $r->reconciliation_status,
            ])->all();

        $byMethod = [];

        foreach ($rows as $row) {
            $byMethod[$row['method']] = Money::add($byMethod[$row['method']] ?? '0', $row['amount']);
        }

        return [[
            'paid_at' => 'Waktu Bayar', 'transaction_number' => 'No. Transaksi', 'receipt_number' => 'No. Kuitansi', 'nim' => 'NIM', 'name' => 'Nama',
            'method' => 'Metode', 'channel' => 'Kanal', 'amount' => 'Nominal', 'channel_fee' => 'Biaya Kanal', 'reconciliation' => 'Rekonsiliasi',
        ], $rows, $this->sums($rows, ['amount', 'channel_fee']) + ['count' => count($rows), 'by_method' => $byMethod]];
    }

    /** @param  array<string, mixed>  $filters */
    private function reportReceivables(array $filters): array
    {
        $rows = $this->query->invoices($filters)->whereIn('invoices.status', ['unpaid', 'partial'])
            ->groupBy('students.id', 'students.nim', 'students.name', 'study_programs.name', 'students.admission_year', 'students.status')
            ->orderByRaw('SUM(invoices.amount - invoices.paid_amount) desc')
            ->get([
                'students.nim', 'students.name', 'study_programs.name as program', 'students.admission_year', 'students.status',
                DB::raw('COUNT(*) as open_invoices'),
                DB::raw('SUM(invoices.amount - invoices.paid_amount) as outstanding'),
                DB::raw('SUM(CASE WHEN invoices.is_overdue = 1 THEN invoices.amount - invoices.paid_amount ELSE 0 END) as arrears'),
                DB::raw('MIN(invoices.due_date) as oldest_due'),
            ])->map(fn ($r) => [
                'nim' => $r->nim, 'name' => $r->name, 'program' => $r->program, 'admission_year' => $r->admission_year, 'student_status' => $r->status,
                'open_invoices' => (int) $r->open_invoices, 'outstanding' => Money::normalize((string) $r->outstanding), 'arrears' => Money::normalize((string) $r->arrears),
                'oldest_due' => $r->oldest_due,
            ])->all();

        return [[
            'nim' => 'NIM', 'name' => 'Nama', 'program' => 'Prodi', 'admission_year' => 'Angkatan', 'student_status' => 'Status Mahasiswa',
            'open_invoices' => 'Tagihan Terbuka', 'outstanding' => 'Piutang', 'arrears' => 'Tunggakan', 'oldest_due' => 'Jatuh Tempo Tertua',
        ], $rows, $this->sums($rows, ['outstanding', 'arrears']) + ['students' => count($rows)]];
    }

    /** @param  array<string, mixed>  $filters */
    private function reportArrears(array $filters): array
    {
        $today = CarbonImmutable::today();
        $rows = $this->query->invoices($filters)->whereIn('invoices.status', ['unpaid', 'partial'])->where('invoices.is_overdue', true)
            ->orderBy('invoices.due_date')->get([
                'invoices.invoice_number', 'students.nim', 'students.name', 'study_programs.name as program', 'invoices.period', 'invoices.due_date',
                'invoices.overdue_since', 'invoices.amount', 'invoices.paid_amount',
            ])->map(fn ($r) => [
                'invoice_number' => $r->invoice_number, 'nim' => $r->nim, 'name' => $r->name, 'program' => $r->program, 'period' => $r->period,
                'due_date' => $r->due_date, 'days_late' => max(0, (int) CarbonImmutable::parse($r->due_date)->diffInDays($today, false)),
                'outstanding' => Money::sub((string) $r->amount, (string) $r->paid_amount),
            ])->all();

        return [[
            'invoice_number' => 'No. Tagihan', 'nim' => 'NIM', 'name' => 'Nama', 'program' => 'Prodi', 'period' => 'Periode', 'due_date' => 'Jatuh Tempo',
            'days_late' => 'Hari Terlambat', 'outstanding' => 'Tunggakan',
        ], $rows, $this->sums($rows, ['outstanding']) + ['count' => count($rows)]];
    }

    /** @param  array<string, mixed>  $filters */
    private function reportAging(array $filters): array
    {
        $rows = $this->dashboard->aging($this->query->invoices($filters)->whereIn('invoices.status', ['unpaid', 'partial']));

        return [['bucket' => 'Umur Piutang', 'count' => 'Jumlah Tagihan', 'amount' => 'Nilai'], $rows, $this->sums($rows, ['amount'])];
    }

    /** @param  array<string, mixed>  $filters */
    private function reportAid(array $filters): array
    {
        $rows = DB::table('invoice_items')
            ->join('invoices', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->join('students', 'students.id', '=', 'invoices.student_id')
            ->leftJoin('study_programs', 'study_programs.id', '=', 'students.study_program_id')
            ->leftJoin('financial_aid_awards', fn ($j) => $j->on('financial_aid_awards.id', '=', 'invoice_items.source_id')->where('invoice_items.source_type', '=', 'financial_aid_award'))
            ->leftJoin('discount_policies', 'discount_policies.id', '=', 'financial_aid_awards.discount_policy_id')
            ->where('invoices.university_id', $this->query->tenantId())
            ->whereNotIn('invoices.status', ['draft', 'cancelled'])
            ->whereIn('invoice_items.line_type', ['aid', 'discount', 'subsidy', 'voucher', 'waiver'])
            ->when($filters['academic_term_id'] ?? null, fn ($q, $v) => $q->where('invoices.academic_term_id', $v))
            ->tap(fn ($q) => $this->query->studentFilters($q, $filters))
            ->groupBy('invoice_items.line_type', 'discount_policies.name', 'discount_policies.funder_name')
            ->get([
                'invoice_items.line_type', 'discount_policies.name as policy', 'discount_policies.funder_name',
                DB::raw('COUNT(DISTINCT invoices.student_id) as students'), DB::raw('SUM(-invoice_items.amount) as total'),
            ])->map(fn ($r) => [
                'type' => match ($r->line_type) {
                    'aid' => 'Beasiswa/Bantuan', 'discount' => 'Potongan', 'subsidy' => 'Subsidi', 'voucher' => 'Voucher', default => 'Pembebasan'
                },
                'policy' => $r->policy ?? ($r->line_type === 'voucher' ? 'Voucher' : ($r->line_type === 'waiver' ? 'Pembebasan tagihan' : '—')),
                'funder' => $r->funder_name ?? '—', 'students' => (int) $r->students, 'total' => Money::normalize((string) $r->total),
            ])->all();

        return [['type' => 'Jenis', 'policy' => 'Nama', 'funder' => 'Penyandang Dana', 'students' => 'Penerima', 'total' => 'Total Keringanan'], $rows, $this->sums($rows, ['total'])];
    }

    /** @param  array<string, mixed>  $filters */
    private function reportPenalties(array $filters): array
    {
        $rows = DB::table('penalty_accruals')
            ->join('invoices', 'invoices.id', '=', 'penalty_accruals.invoice_id')
            ->join('students', 'students.id', '=', 'invoices.student_id')
            ->leftJoin('study_programs', 'study_programs.id', '=', 'students.study_program_id')
            ->join('penalty_rules', 'penalty_rules.id', '=', 'penalty_accruals.penalty_rule_id')
            ->leftJoin('invoices as penalty_invoices', 'penalty_invoices.id', '=', 'penalty_accruals.penalty_invoice_id')
            ->where('penalty_accruals.university_id', $this->query->tenantId())
            ->tap(fn ($q) => $this->query->studentFilters($q, $filters))
            ->orderByDesc('penalty_accruals.updated_at')
            ->get(['invoices.invoice_number', 'students.nim', 'students.name', 'penalty_rules.name as rule', 'penalty_accruals.overdue_from', 'penalty_accruals.periods_counted',
                'penalty_accruals.accrued_amount', 'penalty_accruals.status', 'penalty_invoices.invoice_number as penalty_invoice', 'penalty_invoices.status as penalty_status'])
            ->map(fn ($r) => [
                'invoice_number' => $r->invoice_number, 'nim' => $r->nim, 'name' => $r->name, 'rule' => $r->rule, 'overdue_from' => $r->overdue_from,
                'days' => (int) $r->periods_counted, 'amount' => Money::normalize((string) $r->accrued_amount), 'status' => $r->status,
                'penalty_invoice' => $r->penalty_invoice ?? '—', 'penalty_status' => $r->penalty_status ? (InvoiceStatus::tryFrom($r->penalty_status)?->label() ?? $r->penalty_status) : '—',
            ])->all();

        return [[
            'invoice_number' => 'Tagihan Pokok', 'nim' => 'NIM', 'name' => 'Nama', 'rule' => 'Aturan', 'overdue_from' => 'Terlambat Sejak', 'days' => 'Hari',
            'amount' => 'Denda', 'status' => 'Status Denda', 'penalty_invoice' => 'Tagihan Denda', 'penalty_status' => 'Status Tagihan Denda',
        ], $rows, $this->sums($rows, ['amount'])];
    }

    /** @param  array<string, mixed>  $filters */
    private function reportDeposits(array $filters): array
    {
        $rows = DB::table('student_deposit_accounts')
            ->join('students', 'students.id', '=', 'student_deposit_accounts.student_id')
            ->leftJoin('study_programs', 'study_programs.id', '=', 'students.study_program_id')
            ->where('student_deposit_accounts.university_id', $this->query->tenantId())
            ->where(fn ($q) => $q->where('student_deposit_accounts.balance', '>', 0)->orWhere('student_deposit_accounts.held_amount', '>', 0))
            ->tap(fn ($q) => $this->query->studentFilters($q, $filters))
            ->orderByDesc('student_deposit_accounts.balance')
            ->get(['students.nim', 'students.name', 'study_programs.name as program', 'students.status', 'student_deposit_accounts.balance', 'student_deposit_accounts.held_amount'])
            ->map(fn ($r) => [
                'nim' => $r->nim, 'name' => $r->name, 'program' => $r->program, 'student_status' => $r->status,
                'balance' => Money::normalize((string) $r->balance), 'held' => Money::normalize((string) $r->held_amount),
                'available' => Money::sub((string) $r->balance, (string) $r->held_amount),
            ])->all();

        return [['nim' => 'NIM', 'name' => 'Nama', 'program' => 'Prodi', 'student_status' => 'Status Mahasiswa', 'balance' => 'Saldo', 'held' => 'Ditahan', 'available' => 'Tersedia'],
            $rows, $this->sums($rows, ['balance', 'held', 'available']) + ['accounts' => count($rows)]];
    }

    /** @param  array<string, mixed>  $filters */
    private function reportInstallments(array $filters): array
    {
        $today = CarbonImmutable::today()->toDateString();
        $rows = DB::table('invoice_installments')
            ->join('invoices', 'invoices.id', '=', 'invoice_installments.invoice_id')
            ->join('students', 'students.id', '=', 'invoices.student_id')
            ->leftJoin('study_programs', 'study_programs.id', '=', 'students.study_program_id')
            ->where('invoice_installments.university_id', $this->query->tenantId())
            ->whereNull('invoice_installments.superseded_at')
            ->when($filters['academic_term_id'] ?? null, fn ($q, $v) => $q->where('invoices.academic_term_id', $v))
            ->tap(fn ($q) => $this->query->studentFilters($q, $filters))
            ->orderBy('invoice_installments.due_date')
            ->get(['invoices.invoice_number', 'students.nim', 'students.name', 'invoice_installments.seq', 'invoice_installments.due_date', 'invoice_installments.amount',
                'invoice_installments.paid_amount', 'invoice_installments.status'])
            ->map(fn ($r) => [
                'invoice_number' => $r->invoice_number, 'nim' => $r->nim, 'name' => $r->name, 'seq' => (int) $r->seq, 'due_date' => $r->due_date,
                'amount' => Money::normalize((string) $r->amount), 'paid' => Money::normalize((string) $r->paid_amount),
                'remaining' => Money::sub((string) $r->amount, (string) $r->paid_amount),
                'status' => $r->status === 'paid' ? 'Lunas' : ($r->due_date < $today ? 'Terlambat' : ($r->status === 'partial' ? 'Dibayar Sebagian' : 'Terjadwal')),
            ])->all();

        return [['invoice_number' => 'No. Tagihan', 'nim' => 'NIM', 'name' => 'Nama', 'seq' => 'Termin', 'due_date' => 'Jatuh Tempo', 'amount' => 'Nominal', 'paid' => 'Dibayar', 'remaining' => 'Sisa', 'status' => 'Status'],
            $rows, $this->sums($rows, ['amount', 'paid', 'remaining'])];
    }

    /** @param  array<string, mixed>  $filters */
    private function reportTransactions(array $filters): array
    {
        $rows = $this->query->transactions(array_diff_key($filters, array_flip(['date_from', 'date_to'])))
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->where('payment_transactions.created_at', '>=', $v.' 00:00:00'))
            ->when($filters['date_to'] ?? null, fn ($q, $v) => $q->where('payment_transactions.created_at', '<=', $v.' 23:59:59'))
            ->when($filters['transaction_status'] ?? null, fn ($q, $v) => $q->where('payment_transactions.status', $v))
            ->orderByDesc('payment_transactions.created_at')
            ->get(['payment_transactions.created_at', 'payment_transactions.transaction_number', 'students.nim', 'students.name', 'payment_transactions.method',
                'payment_channels.name as channel', 'payment_transactions.amount', 'payment_transactions.status', 'payment_transactions.reconciliation_status', 'payment_transactions.paid_at'])
            ->map(fn ($r) => [
                'created_at' => (string) $r->created_at, 'transaction_number' => $r->transaction_number, 'nim' => $r->nim, 'name' => $r->name,
                'method' => PaymentMethod::tryFrom((string) $r->method)?->label() ?? $r->method, 'channel' => $r->channel ?? '—',
                'amount' => Money::normalize((string) $r->amount), 'status' => TransactionStatus::tryFrom((string) $r->status)?->label() ?? $r->status,
                'reconciliation' => $r->reconciliation_status, 'paid_at' => $r->paid_at ? (string) $r->paid_at : '—',
            ])->all();

        return [['created_at' => 'Dibuat', 'transaction_number' => 'No. Transaksi', 'nim' => 'NIM', 'name' => 'Nama', 'method' => 'Metode', 'channel' => 'Kanal',
            'amount' => 'Nominal', 'status' => 'Status', 'reconciliation' => 'Rekonsiliasi', 'paid_at' => 'Waktu Bayar'], $rows, ['count' => count($rows)]];
    }

    /** @param  array<string, mixed>  $filters */
    private function reportReconciliation(array $filters): array
    {
        $rows = DB::table('reconciliation_batches')->where('university_id', $this->query->tenantId())
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->where('statement_date', '>=', $v))
            ->when($filters['date_to'] ?? null, fn ($q, $v) => $q->where('statement_date', '<=', $v))
            ->orderByDesc('statement_date')
            ->get(['batch_number', 'source', 'statement_date', 'status', 'total_items', 'matched_items', 'exception_items', 'total_amount'])
            ->map(fn ($r) => ['batch_number' => $r->batch_number, 'source' => $r->source, 'statement_date' => $r->statement_date, 'status' => $r->status,
                'total_items' => (int) $r->total_items, 'matched_items' => (int) $r->matched_items, 'exception_items' => (int) $r->exception_items,
                'total_amount' => Money::normalize((string) $r->total_amount)])->all();

        return [['batch_number' => 'No. Batch', 'source' => 'Sumber', 'statement_date' => 'Tanggal Mutasi', 'status' => 'Status', 'total_items' => 'Baris',
            'matched_items' => 'Cocok', 'exception_items' => 'Pengecualian Terbuka', 'total_amount' => 'Total Mutasi'], $rows, $this->sums($rows, ['total_amount'])];
    }

    /** @param  array<string, mixed>  $filters */
    private function reportRefunds(array $filters): array
    {
        $rows = DB::table('refunds')->join('students', 'students.id', '=', 'refunds.student_id')
            ->leftJoin('study_programs', 'study_programs.id', '=', 'students.study_program_id')
            ->where('refunds.university_id', $this->query->tenantId())
            ->tap(fn ($q) => $this->query->studentFilters($q, $filters))
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->where('refunds.created_at', '>=', $v.' 00:00:00'))
            ->when($filters['date_to'] ?? null, fn ($q, $v) => $q->where('refunds.created_at', '<=', $v.' 23:59:59'))
            ->orderByDesc('refunds.created_at')
            ->get(['refunds.refund_number', 'students.nim', 'students.name', 'refunds.amount', 'refunds.reason_code', 'refunds.status', 'refunds.created_at', 'refunds.executed_at'])
            ->map(fn ($r) => ['refund_number' => $r->refund_number, 'nim' => $r->nim, 'name' => $r->name, 'amount' => Money::normalize((string) $r->amount),
                'reason' => RefundService::REASONS[$r->reason_code] ?? $r->reason_code, 'status' => $r->status, 'created_at' => (string) $r->created_at,
                'executed_at' => $r->executed_at ? (string) $r->executed_at : '—'])->all();

        return [['refund_number' => 'No. Refund', 'nim' => 'NIM', 'name' => 'Nama', 'amount' => 'Nominal', 'reason' => 'Alasan', 'status' => 'Status',
            'created_at' => 'Diajukan', 'executed_at' => 'Dilaksanakan'], $rows, $this->sums($rows, ['amount'])];
    }

    /** @param  array<string, mixed>  $filters */
    private function reportOfficerActivity(array $filters): array
    {
        $types = array_map(fn ($c) => 'Modules\\Finance\\Models\\'.$c, ['Invoice', 'InvoiceAdjustment', 'PaymentTransaction', 'Refund', 'BillingRun', 'CashierSession',
            'ReconciliationBatch', 'FinancialAidAward', 'PenaltyRule', 'PenaltyAccrual', 'FeeRateTable', 'StudentUktAssignment', 'Voucher']);
        $types[] = 'Modules\\Academic\\Models\\AcademicBlock';

        $rows = DB::table('audit_logs')->leftJoin('users', 'users.id', '=', 'audit_logs.user_id')
            ->where('audit_logs.university_id', $this->query->tenantId())
            ->whereIn('audit_logs.auditable_type', $types)
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->where('audit_logs.created_at', '>=', $v.' 00:00:00'))
            ->when($filters['date_to'] ?? null, fn ($q, $v) => $q->where('audit_logs.created_at', '<=', $v.' 23:59:59'))
            ->orderByDesc('audit_logs.created_at')->limit(5000)
            ->get(['audit_logs.created_at', 'users.name as officer', 'audit_logs.action', 'audit_logs.auditable_type', 'audit_logs.auditable_id', 'audit_logs.reason'])
            ->map(fn ($r) => ['created_at' => (string) $r->created_at, 'officer' => $r->officer ?? 'Sistem', 'action' => $r->action,
                'object' => class_basename((string) $r->auditable_type), 'object_id' => $r->auditable_id, 'reason' => $r->reason ?? '—'])->all();

        $byOfficer = [];

        foreach ($rows as $row) {
            $byOfficer[$row['officer']] = ($byOfficer[$row['officer']] ?? 0) + 1;
        }

        return [['created_at' => 'Waktu', 'officer' => 'Petugas', 'action' => 'Aksi', 'object' => 'Objek', 'object_id' => 'ID Objek', 'reason' => 'Alasan'], $rows, ['count' => count($rows), 'by_officer' => $byOfficer]];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<int, string>  $keys
     * @return array<string, string>
     */
    private function sums(array $rows, array $keys): array
    {
        $totals = [];

        foreach ($keys as $key) {
            $totals['total_'.$key] = Money::fromCents(array_sum(array_map(fn ($r) => Money::toCents((string) ($r[$key] ?? 0)), $rows)));
        }

        return $totals;
    }
}
