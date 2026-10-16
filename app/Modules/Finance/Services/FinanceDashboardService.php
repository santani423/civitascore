<?php

namespace Modules\Finance\Services;

use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Modules\Finance\Support\Money;

/**
 * Dashboard Keuangan (rancangan KEU §11.2). Semua angka diturunkan dari
 * tagihan & transaksi dengan definisi §11.1: penerimaan = transaksi
 * sukses non-deposit (deposit bukan uang masuk baru), piutang = sisa
 * tagihan terbuka, tunggakan = piutang lewat jatuh tempo + tenggang.
 */
class FinanceDashboardService
{
    public function __construct(private readonly FinanceQuery $query) {}

    /** @param  array<string, mixed>  $filters */
    public function build(array $filters): array
    {
        $from = $filters['date_from'] ?? CarbonImmutable::today()->subDays(29)->toDateString();
        $to = $filters['date_to'] ?? CarbonImmutable::today()->toDateString();
        $filters = [...$filters, 'date_from' => $from, 'date_to' => $to];

        $invoices = $this->query->invoices($filters);
        $open = (clone $invoices)->whereIn('invoices.status', ['unpaid', 'partial']);
        $billed = FinanceQuery::sum($invoices, 'invoices.amount');
        $paidOnInvoices = FinanceQuery::sum($invoices, 'invoices.paid_amount');
        $receivable = FinanceQuery::sum($open, 'invoices.amount - invoices.paid_amount');
        $arrears = FinanceQuery::sum((clone $open)->where('invoices.is_overdue', true), 'invoices.amount - invoices.paid_amount');
        $soon = (clone $open)->where('invoices.is_overdue', false)->whereBetween('invoices.due_date', [CarbonImmutable::today()->toDateString(), CarbonImmutable::today()->addDays(7)->toDateString()]);
        $receipts = $this->query->transactions($filters)->where('payment_transactions.status', 'success')->where('payment_transactions.method', '!=', 'deposit');
        $tenant = $this->query->tenantId();

        return [
            'filters' => $filters,
            'summary' => [
                'billed' => $billed,
                'gross' => FinanceQuery::sum($invoices, 'invoices.gross_amount'),
                'discount' => FinanceQuery::sum($invoices, 'invoices.discount_amount'),
                'paid_on_invoices' => $paidOnInvoices,
                'received' => FinanceQuery::sum($receipts, 'payment_transactions.amount'),
                'received_count' => (clone $receipts)->count(),
                'receivable' => $receivable,
                'arrears' => $arrears,
                'overdue_invoices' => (clone $open)->where('invoices.is_overdue', true)->count(),
                'due_soon_count' => (clone $soon)->count(),
                'due_soon_amount' => FinanceQuery::sum($soon, 'invoices.amount - invoices.paid_amount'),
                'collection_rate' => Money::toCents($billed) > 0 ? round(Money::toCents($paidOnInvoices) / Money::toCents($billed) * 100, 1) : null,
                'deposit_balance' => number_format((float) DB::table('student_deposit_accounts')->where('university_id', $tenant)->sum('balance'), 2, '.', ''),
                'pending_proofs' => DB::table('payment_proofs')->where('university_id', $tenant)->where('status', 'submitted')->count(),
                'stale_online' => DB::table('payment_transactions')->where('university_id', $tenant)->where('status', 'pending')->where('method', 'online')->where('created_at', '<', now()->subDay())->count(),
                'reconciliation_exceptions' => DB::table('reconciliation_items')->where('university_id', $tenant)->where('result', '!=', 'matched')->whereNull('resolution')->count(),
                'refunds_pending' => DB::table('refunds')->where('university_id', $tenant)->whereIn('status', ['pending_approval', 'approved'])->count(),
                'blocked_students' => DB::table('academic_blocks')->where('university_id', $tenant)->where('source', 'finance')->where('status', 'active')->distinct()->count('student_id'),
                'pending_approvals' => DB::table('invoice_adjustments')->where('university_id', $tenant)->where('status', 'pending_approval')->count()
                    + DB::table('billing_runs')->where('university_id', $tenant)->where('status', 'pending_approval')->count()
                    + DB::table('financial_aid_awards')->where('university_id', $tenant)->where('status', 'pending_approval')->count()
                    + DB::table('installment_requests')->where('university_id', $tenant)->where('status', 'submitted')->count(),
            ],
            'by_faculty' => $this->breakdown($invoices, 'faculties.name'),
            'by_program' => $this->breakdown($invoices, 'study_programs.name'),
            'by_cohort' => $this->breakdown($invoices, 'students.admission_year'),
            'by_student_status' => $this->breakdown($invoices, 'students.status'),
            'receipts_trend' => $this->trend($receipts, $from, $to),
            'aging' => $this->aging($open),
            'recent_transactions' => (clone $receipts)->orderByDesc('payment_transactions.paid_at')->limit(10)->get([
                'payment_transactions.id', 'payment_transactions.transaction_number', 'payment_transactions.receipt_number', 'payment_transactions.amount',
                'payment_transactions.method', 'payment_transactions.paid_at', 'students.nim', 'students.name as student_name',
            ]),
            'follow_up' => (clone $open)->where('invoices.is_overdue', true)->orderByRaw('(invoices.amount - invoices.paid_amount) desc')->limit(10)->get([
                'invoices.id', 'invoices.invoice_number', 'invoices.period', 'invoices.due_date', 'invoices.overdue_since',
                DB::raw('(invoices.amount - invoices.paid_amount) as outstanding'), 'students.id as student_id', 'students.nim', 'students.name as student_name',
            ]),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function breakdown(Builder $invoices, string $column): array
    {
        return (clone $invoices)->groupBy($column)->orderBy($column)->get([
            DB::raw("{$column} as label"),
            DB::raw('COUNT(*) as invoices'),
            DB::raw('COALESCE(SUM(invoices.amount), 0) as billed'),
            DB::raw('COALESCE(SUM(invoices.paid_amount), 0) as paid'),
            DB::raw("COALESCE(SUM(CASE WHEN invoices.status IN ('unpaid','partial') THEN invoices.amount - invoices.paid_amount ELSE 0 END), 0) as outstanding"),
            DB::raw("COALESCE(SUM(CASE WHEN invoices.status IN ('unpaid','partial') AND invoices.is_overdue = 1 THEN invoices.amount - invoices.paid_amount ELSE 0 END), 0) as arrears"),
        ])->map(fn ($r) => [
            'label' => (string) ($r->label ?? '—'),
            'invoices' => (int) $r->invoices,
            'billed' => Money::normalize((string) $r->billed),
            'paid' => Money::normalize((string) $r->paid),
            'outstanding' => Money::normalize((string) $r->outstanding),
            'arrears' => Money::normalize((string) $r->arrears),
        ])->all();
    }

    /** @return array<int, array{date: string, amount: string, count: int}> */
    private function trend(Builder $receipts, string $from, string $to): array
    {
        $monthly = CarbonImmutable::parse($from)->diffInDays(CarbonImmutable::parse($to)) > 62;
        $buckets = [];

        foreach ((clone $receipts)->get(['payment_transactions.paid_at', 'payment_transactions.amount']) as $row) {
            $key = substr((string) $row->paid_at, 0, $monthly ? 7 : 10);
            $buckets[$key] ??= ['date' => $key, 'cents' => 0, 'count' => 0];
            $buckets[$key]['cents'] += Money::toCents((string) $row->amount);
            $buckets[$key]['count']++;
        }

        ksort($buckets);

        return array_values(array_map(fn ($b) => ['date' => $b['date'], 'amount' => Money::fromCents($b['cents']), 'count' => $b['count']], $buckets));
    }

    /** Aging berdasarkan jatuh tempo tagihan (§8.3.8). @return array<int, array{bucket: string, count: int, amount: string}> */
    public function aging(Builder $open): array
    {
        $labels = ['current' => 'Belum jatuh tempo', '1_30' => '1–30 hari', '31_60' => '31–60 hari', '61_90' => '61–90 hari', '91_180' => '91–180 hari', 'over_180' => '> 180 hari'];
        $result = array_map(fn ($label) => ['bucket' => $label, 'count' => 0, 'cents' => 0], $labels);
        $today = CarbonImmutable::today();

        foreach ((clone $open)->get(['invoices.due_date', 'invoices.amount', 'invoices.paid_amount']) as $row) {
            $days = (int) CarbonImmutable::parse($row->due_date)->diffInDays($today, false);
            $key = match (true) {
                $days <= 0 => 'current',
                $days <= 30 => '1_30',
                $days <= 60 => '31_60',
                $days <= 90 => '61_90',
                $days <= 180 => '91_180',
                default => 'over_180',
            };
            $result[$key]['count']++;
            $result[$key]['cents'] += Money::toCents((string) $row->amount) - Money::toCents((string) $row->paid_amount);
        }

        return array_values(array_map(fn ($r) => ['bucket' => $r['bucket'], 'count' => $r['count'], 'amount' => Money::fromCents($r['cents'])], $result));
    }
}
