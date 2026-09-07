<?php

namespace Modules\Tenancy\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Modules\Academic\Models\Employee;
use Modules\Academic\Models\Lecturer;
use Modules\Academic\Models\Student;
use Modules\Academic\Models\StudyProgram;
use Modules\ApprovalWorkflow\Enums\ApprovalRequestStatus;
use Modules\ApprovalWorkflow\Models\ApprovalRequest;
use Modules\Finance\Enums\InvoiceStatus;
use Modules\Finance\Models\Invoice;
use Modules\Tenancy\Enums\UniversityStatus;
use Modules\Tenancy\Models\University;
use Modules\Tenancy\Models\UserUniversity;

class PlatformStatisticsController extends Controller
{
    private const CACHE_TTL_SECONDS = 300;

    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->hasPermissionTo('platform_statistics.read'), 403, 'Anda tidak memiliki izin untuk mengakses resource ini.');

        $stats = Cache::remember('platform:statistics', self::CACHE_TTL_SECONDS, fn () => $this->build());

        return ApiResponse::success($stats);
    }

    /**
     * Every count here runs with no resolved tenant (platform context), so
     * TenantScoped's global scope applies no restriction — these are
     * genuinely cross-university sums, not a bypass.
     */
    private function build(): array
    {
        $byStatus = University::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $unpaidInvoiceStatuses = [InvoiceStatus::Unpaid, InvoiceStatus::Partial];
        $pendingApprovalStatuses = [ApprovalRequestStatus::Submitted, ApprovalRequestStatus::InProgress];

        $studentsByUniversity = Student::query()->selectRaw('university_id, count(*) as total')->groupBy('university_id')->pluck('total', 'university_id');
        $lecturersByUniversity = Lecturer::query()->selectRaw('university_id, count(*) as total')->groupBy('university_id')->pluck('total', 'university_id');
        $employeesByUniversity = Employee::query()->selectRaw('university_id, count(*) as total')->groupBy('university_id')->pluck('total', 'university_id');
        $unpaidInvoicesByUniversity = Invoice::query()->whereIn('status', $unpaidInvoiceStatuses)->selectRaw('university_id, count(*) as total')->groupBy('university_id')->pluck('total', 'university_id');
        $pendingApprovalsByUniversity = ApprovalRequest::query()->whereIn('status', $pendingApprovalStatuses)->selectRaw('university_id, count(*) as total')->groupBy('university_id')->pluck('total', 'university_id');

        // ->all() below is load-bearing — see the identical note in
        // DashboardStatsService::buildStats(): a bare Collection surviving
        // inside this cached array can come back from Redis corrupted on a
        // cache *hit*, which then json_encode()s as an object instead of an
        // array and breaks any frontend .map() over it.
        $byUniversity = University::query()->orderBy('name')->get(['id', 'name'])->map(fn (University $university) => [
            'id' => $university->id,
            'name' => $university->name,
            'students' => (int) ($studentsByUniversity[$university->id] ?? 0),
            'lecturers' => (int) ($lecturersByUniversity[$university->id] ?? 0),
            'employees' => (int) ($employeesByUniversity[$university->id] ?? 0),
            'unpaid_invoices' => (int) ($unpaidInvoicesByUniversity[$university->id] ?? 0),
            'pending_approvals' => (int) ($pendingApprovalsByUniversity[$university->id] ?? 0),
        ])->all();

        return [
            'universities_total' => University::query()->count(),
            'universities_active' => University::query()->where('status', UniversityStatus::Active)->count(),
            'universities_by_status' => $byStatus->all(),
            'users_total' => User::query()->count(),
            'memberships_total' => UserUniversity::query()->count(),
            'students_total' => $studentsByUniversity->sum(),
            'lecturers_total' => $lecturersByUniversity->sum(),
            'employees_total' => $employeesByUniversity->sum(),
            'study_programs_total' => StudyProgram::query()->count(),
            'unpaid_invoices_total' => $unpaidInvoicesByUniversity->sum(),
            'pending_approvals_total' => $pendingApprovalsByUniversity->sum(),
            'by_university' => $byUniversity,
        ];
    }
}
