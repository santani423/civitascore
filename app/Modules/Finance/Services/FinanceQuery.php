<?php

namespace Modules\Finance\Services;

use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Query agregat KEU berbasis query builder dengan filter tenant eksplisit
 * (`invoices.university_id`) — global scope Eloquent memakai kolom tanpa
 * prefiks tabel sehingga tidak aman untuk join. Filter laporan seragam
 * untuk dashboard & laporan agar angkanya konsisten (rancangan KEU §11.1).
 */
class FinanceQuery
{
    public function __construct(private readonly TenantContext $tenant) {}

    public function tenantId(): string
    {
        return (string) $this->tenant->universityId();
    }

    /** @param  array<string, mixed>  $filters */
    public function invoices(array $filters, bool $includeInactive = false): Builder
    {
        return DB::table('invoices')
            ->join('students', 'students.id', '=', 'invoices.student_id')
            ->leftJoin('study_programs', 'study_programs.id', '=', 'students.study_program_id')
            ->leftJoin('faculties', 'faculties.id', '=', 'study_programs.faculty_id')
            ->where('invoices.university_id', $this->tenantId())
            ->when(! $includeInactive, fn (Builder $q) => $q->whereNotIn('invoices.status', ['draft', 'cancelled']))
            ->when($filters['academic_term_id'] ?? null, fn (Builder $q, $v) => $q->where('invoices.academic_term_id', $v))
            ->when($filters['bill_group'] ?? null, fn (Builder $q, $v) => $q->where('invoices.bill_group', $v))
            ->when($filters['status'] ?? null, fn (Builder $q, $v) => $q->whereIn('invoices.status', explode(',', (string) $v)))
            ->tap(fn (Builder $q) => $this->studentFilters($q, $filters));
    }

    /** @param  array<string, mixed>  $filters */
    public function transactions(array $filters): Builder
    {
        return DB::table('payment_transactions')
            ->join('students', 'students.id', '=', 'payment_transactions.student_id')
            ->leftJoin('study_programs', 'study_programs.id', '=', 'students.study_program_id')
            ->leftJoin('faculties', 'faculties.id', '=', 'study_programs.faculty_id')
            ->leftJoin('payment_channels', 'payment_channels.id', '=', 'payment_transactions.payment_channel_id')
            ->where('payment_transactions.university_id', $this->tenantId())
            ->when($filters['date_from'] ?? null, fn (Builder $q, $v) => $q->where('payment_transactions.paid_at', '>=', $v.' 00:00:00'))
            ->when($filters['date_to'] ?? null, fn (Builder $q, $v) => $q->where('payment_transactions.paid_at', '<=', $v.' 23:59:59'))
            ->when($filters['method'] ?? null, fn (Builder $q, $v) => $q->where('payment_transactions.method', $v))
            ->tap(fn (Builder $q) => $this->studentFilters($q, $filters));
    }

    /** @param  array<string, mixed>  $filters */
    public function studentFilters(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['study_program_id'] ?? null, fn (Builder $q, $v) => $q->where('students.study_program_id', $v))
            ->when($filters['faculty_id'] ?? null, fn (Builder $q, $v) => $q->where('study_programs.faculty_id', $v))
            ->when($filters['admission_year'] ?? null, fn (Builder $q, $v) => $q->where('students.admission_year', (int) $v))
            ->when($filters['student_status'] ?? null, fn (Builder $q, $v) => $q->where('students.status', $v))
            ->when($filters['search'] ?? null, fn (Builder $q, $v) => $q->where(fn (Builder $w) => $w->where('students.nim', 'like', "%{$v}%")->orWhere('students.name', 'like', "%{$v}%")));
    }

    /** SUM aman lintas MySQL/SQLite, dikembalikan sebagai string desimal. */
    public static function sum(Builder $query, string $expression): string
    {
        $value = (clone $query)->selectRaw("COALESCE(SUM({$expression}), 0) as total")->value('total');

        return number_format((float) $value, 2, '.', '');
    }
}
