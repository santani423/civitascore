<?php

namespace Modules\Dashboard\Services;

use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Academic\Enums\StudentStatus;
use Modules\Academic\Models\AcademicTerm;
use Modules\Academic\Models\ClassSection;
use Modules\Academic\Models\Employee;
use Modules\Academic\Models\Faculty;
use Modules\Academic\Models\Lecturer;
use Modules\Academic\Models\Student;
use Modules\Academic\Models\StudyProgram;
use Modules\ApprovalWorkflow\Enums\ApprovalRequestStatus;
use Modules\ApprovalWorkflow\Models\ApprovalRequest;
use Modules\Finance\Enums\InvoiceStatus;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\Payment;

/**
 * Pure tenant-wide aggregation (never permission/user-aware — that's
 * DashboardController's job, applied *after* reading from cache) so the
 * same cached blob serves every viewer of the same tenant+filter
 * combination. `pending_approvals` is deliberately NOT computed here — its
 * count depends on which user is asking (own submissions vs tenant-wide,
 * same branching as ApprovalRequestController::index()), so it can't be
 * cached at tenant level; DashboardController computes it separately.
 *
 * Relies entirely on TenantScoped's global scope for university isolation
 * (every model queried here uses it) — no explicit university_id filtering
 * needed in the queries themselves.
 */
class DashboardStatsService
{
    private const CACHE_TTL_SECONDS = 300;

    public function __construct(private readonly TenantContext $tenant) {}

    /**
     * @param  array{academic_term_id?: ?string, faculty_id?: ?string, study_program_id?: ?string, date_from?: ?string, date_to?: ?string}  $filters
     * @return array{summary: array<string, int>, charts: array<string, array<int, array<string, mixed>>>, filters: array<string, mixed>}
     */
    public function compute(array $filters): array
    {
        $cacheKey = 'dashboard:stats:'.($this->tenant->universityId() ?? 'platform').':'.md5(json_encode($filters));

        return Cache::tags(['dashboard_stats'])->remember(
            $cacheKey,
            self::CACHE_TTL_SECONDS,
            fn (): array => $this->buildStats($filters),
        );
    }

    /**
     * @param  array{academic_term_id?: ?string, faculty_id?: ?string, study_program_id?: ?string, date_from?: ?string, date_to?: ?string}  $filters
     */
    private function buildStats(array $filters): array
    {
        $studentQuery = $this->scopedStudentQuery($filters);
        $classQuery = $this->scopedClassQuery($filters);

        return [
            'summary' => [
                'total_students' => (clone $studentQuery)->count(),
                'total_lecturers' => Lecturer::query()->count(),
                'total_employees' => Employee::query()->count(),
                'total_study_programs' => StudyProgram::query()->count(),
                'active_students' => (clone $studentQuery)->where('status', StudentStatus::Active)->count(),
                'active_classes' => (clone $classQuery)->where('is_active', true)->count(),
                'unpaid_invoices' => Invoice::query()->whereIn('status', [InvoiceStatus::Unpaid, InvoiceStatus::Partial])->count(),
            ],
            'charts' => [
                'student_growth' => $this->studentGrowth($studentQuery),
                'student_status' => $this->studentStatus($studentQuery),
                'students_by_program' => $this->studentsByProgram($studentQuery),
                'staff_by_unit' => $this->staffByUnit(),
                'payment_trend' => $this->paymentTrend($filters),
                'invoice_status' => $this->invoiceStatus(),
                'active_classes_by_program' => $this->activeClassesByProgram($classQuery),
                'approval_status' => $this->approvalStatus(),
            ],
            // ->all() on every branch below is load-bearing, not stylistic:
            // this whole array gets stored via Cache::remember(). A bare
            // Collection (or Eloquent Model) left inside it can come back
            // from Redis as a corrupted __PHP_Incomplete_Class_Name stub on
            // a cache *hit* (unserialize() losing the class), which then
            // json_encode()s as a JS object instead of an array and crashes
            // any frontend code that .map()s over it. Plain arrays have no
            // such failure mode.
            'filters' => [
                'academic_terms' => AcademicTerm::query()
                    ->orderByDesc('academic_year')->orderByDesc('semester')
                    ->get(['id', 'academic_year', 'semester', 'is_current'])
                    ->map(fn (AcademicTerm $term) => [
                        'id' => $term->id,
                        'label' => $term->label(),
                        'is_current' => $term->is_current,
                    ])
                    ->all(),
                'faculties' => Faculty::query()->orderBy('name')->get(['id', 'name'])
                    ->map(fn (Faculty $faculty) => ['id' => $faculty->id, 'name' => $faculty->name])
                    ->all(),
                'study_programs' => StudyProgram::query()->orderBy('name')->get(['id', 'name', 'faculty_id'])
                    ->map(fn (StudyProgram $program) => ['id' => $program->id, 'name' => $program->name, 'faculty_id' => $program->faculty_id])
                    ->all(),
            ],
        ];
    }

    /**
     * @param  array{faculty_id?: ?string, study_program_id?: ?string}  $filters
     * @return \Illuminate\Database\Eloquent\Builder<Student>
     */
    private function scopedStudentQuery(array $filters): \Illuminate\Database\Eloquent\Builder
    {
        $query = Student::query();

        if (! empty($filters['study_program_id'])) {
            $query->where('study_program_id', $filters['study_program_id']);
        } elseif (! empty($filters['faculty_id'])) {
            $query->whereHas('studyProgram', fn ($q) => $q->where('faculty_id', $filters['faculty_id']));
        }

        return $query;
    }

    /**
     * @param  array{academic_term_id?: ?string, faculty_id?: ?string, study_program_id?: ?string}  $filters
     * @return \Illuminate\Database\Eloquent\Builder<ClassSection>
     */
    private function scopedClassQuery(array $filters): \Illuminate\Database\Eloquent\Builder
    {
        $query = ClassSection::query();

        $termId = $filters['academic_term_id'] ?? AcademicTerm::query()->where('is_current', true)->value('id');

        if ($termId) {
            $query->where('academic_term_id', $termId);
        }

        if (! empty($filters['study_program_id'])) {
            $query->where('study_program_id', $filters['study_program_id']);
        } elseif (! empty($filters['faculty_id'])) {
            $query->whereHas('studyProgram', fn ($q) => $q->where('faculty_id', $filters['faculty_id']));
        }

        return $query;
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Student>  $studentQuery
     * @return array<int, array{year: int, total: int}>
     */
    private function studentGrowth(\Illuminate\Database\Eloquent\Builder $studentQuery): array
    {
        return (clone $studentQuery)
            ->selectRaw('admission_year as year, count(*) as total')
            ->groupBy('admission_year')
            ->orderBy('admission_year')
            ->get()
            ->map(fn ($row) => ['year' => (int) $row->year, 'total' => (int) $row->total])
            ->all();
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Student>  $studentQuery
     * @return array<int, array{status: string, total: int}>
     */
    private function studentStatus(\Illuminate\Database\Eloquent\Builder $studentQuery): array
    {
        $labels = [
            StudentStatus::Active->value => 'Aktif',
            StudentStatus::Leave->value => 'Cuti',
            StudentStatus::Graduated->value => 'Lulus',
            StudentStatus::Inactive->value => 'Nonaktif',
            StudentStatus::DroppedOut->value => 'Drop Out',
        ];

        return (clone $studentQuery)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->get()
            ->map(function ($row) use ($labels) {
                // Eloquent casts `status` to StudentStatus on access even for a
                // selectRaw()'d column (it matches a real cast column name) —
                // ->value gets back the plain string the $labels map is keyed by.
                $status = $row->status instanceof StudentStatus ? $row->status->value : $row->status;

                return ['status' => $labels[$status] ?? $status, 'total' => (int) $row->total];
            })
            ->all();
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Student>  $studentQuery
     * @return array<int, array{program: string, total: int}>
     */
    private function studentsByProgram(\Illuminate\Database\Eloquent\Builder $studentQuery): array
    {
        // Deliberately not a SQL join: `students` and `study_programs` are
        // both TenantScoped, and BelongsToInstitutionScope adds an
        // unqualified `university_id` condition (see its docblock) — once
        // two such tables are joined, MySQL can't tell which table's
        // column that refers to ("Column 'university_id' ... is
        // ambiguous"). Grouping by the FK then mapping names in PHP (tiny
        // lookup, a handful of study programs per tenant) sidesteps it
        // without touching the shared scope used by every tenant model.
        $countsByProgramId = (clone $studentQuery)
            ->selectRaw('study_program_id, count(*) as total')
            ->groupBy('study_program_id')
            ->pluck('total', 'study_program_id');

        return $this->namedCounts($countsByProgramId, StudyProgram::query()->pluck('name', 'id'), 'program');
    }

    /**
     * @return array<int, array{unit: string, lecturers: int, employees: int}>
     */
    private function staffByUnit(): array
    {
        // Same reasoning as studentsByProgram() re: no join across two
        // TenantScoped tables.
        $lecturerCountsByFacultyId = Lecturer::query()
            ->whereNotNull('faculty_id')
            ->selectRaw('faculty_id, count(*) as total')
            ->groupBy('faculty_id')
            ->pluck('total', 'faculty_id');

        $facultyNames = Faculty::query()->pluck('name', 'id');
        $lecturersByUnit = $lecturerCountsByFacultyId->mapWithKeys(
            fn ($total, $facultyId) => [($facultyNames[$facultyId] ?? $facultyId) => $total],
        );

        $employeesByUnit = Employee::query()
            ->selectRaw('unit_kerja as unit, count(*) as total')
            ->groupBy('unit_kerja')
            ->pluck('total', 'unit');

        $units = $lecturersByUnit->keys()->merge($employeesByUnit->keys())->unique()->sort()->values();

        return $units
            ->map(fn ($unit) => [
                'unit' => $unit,
                'lecturers' => (int) ($lecturersByUnit[$unit] ?? 0),
                'employees' => (int) ($employeesByUnit[$unit] ?? 0),
            ])
            ->all();
    }

    /**
     * @param  array{date_from?: ?string, date_to?: ?string}  $filters
     * @return array<int, array{month: string, total: string}>
     */
    private function paymentTrend(array $filters): array
    {
        $query = Payment::query();

        if (! empty($filters['date_from'])) {
            $query->whereDate('paid_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('paid_at', '<=', $filters['date_to']);
        }

        if (empty($filters['date_from']) && empty($filters['date_to'])) {
            $query->where('paid_at', '>=', now()->subMonths(9)->startOfMonth());
        }

        // MySQL in production, SQLite under Pest (see phpunit.xml) — the
        // month-bucketing function isn't portable, everything else here is.
        $monthExpression = DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', paid_at)"
            : "DATE_FORMAT(paid_at, '%Y-%m')";

        return $query
            ->selectRaw("{$monthExpression} as month, sum(amount) as total")
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(fn ($row) => ['month' => $row->month, 'total' => (string) $row->total])
            ->all();
    }

    /**
     * @return array<int, array{status: string, total: int, amount: string}>
     */
    private function invoiceStatus(): array
    {
        $labels = [
            InvoiceStatus::Paid->value => 'Lunas',
            InvoiceStatus::Partial->value => 'Dibayar Sebagian',
            InvoiceStatus::Unpaid->value => 'Belum Dibayar',
        ];

        return Invoice::query()
            ->selectRaw('status, count(*) as total, sum(amount) as amount_total')
            ->groupBy('status')
            ->get()
            ->map(function ($row) use ($labels) {
                $status = $row->status instanceof InvoiceStatus ? $row->status->value : $row->status;

                return [
                    'status' => $labels[$status] ?? $status,
                    'total' => (int) $row->total,
                    'amount' => (string) $row->amount_total,
                ];
            })
            ->all();
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<ClassSection>  $classQuery
     * @return array<int, array{program: string, total: int}>
     */
    private function activeClassesByProgram(\Illuminate\Database\Eloquent\Builder $classQuery): array
    {
        // No join — see studentsByProgram().
        $countsByProgramId = (clone $classQuery)
            ->where('is_active', true)
            ->selectRaw('study_program_id, count(*) as total')
            ->groupBy('study_program_id')
            ->pluck('total', 'study_program_id');

        return $this->namedCounts($countsByProgramId, StudyProgram::query()->pluck('name', 'id'), 'program');
    }

    /**
     * @param  \Illuminate\Support\Collection<string, int>  $countsById  id => count
     * @param  \Illuminate\Support\Collection<string, string>  $namesById  id => display name
     * @return array<int, array<string, mixed>>
     */
    private function namedCounts(\Illuminate\Support\Collection $countsById, \Illuminate\Support\Collection $namesById, string $labelKey): array
    {
        return $countsById
            ->map(fn ($total, $id) => [$labelKey => $namesById[$id] ?? $id, 'total' => (int) $total])
            ->values()
            ->sortByDesc('total')
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{status: string, total: int}>
     */
    private function approvalStatus(): array
    {
        $buckets = ApprovalRequest::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->get()
            ->mapWithKeys(fn ($row) => [
                ($row->status instanceof ApprovalRequestStatus ? $row->status->value : $row->status) => $row->total,
            ]);

        $menunggu = (int) ($buckets[ApprovalRequestStatus::Submitted->value] ?? 0)
            + (int) ($buckets[ApprovalRequestStatus::InProgress->value] ?? 0);

        return [
            ['status' => 'Disetujui', 'total' => (int) ($buckets[ApprovalRequestStatus::Approved->value] ?? 0)],
            ['status' => 'Ditolak', 'total' => (int) ($buckets[ApprovalRequestStatus::Rejected->value] ?? 0)],
            ['status' => 'Menunggu', 'total' => $menunggu],
        ];
    }
}
