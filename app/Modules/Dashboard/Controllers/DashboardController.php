<?php

namespace Modules\Dashboard\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\ApprovalWorkflow\Enums\ApprovalRequestStatus;
use Modules\ApprovalWorkflow\Models\ApprovalRequest;
use Modules\Dashboard\Services\DashboardStatsService;

class DashboardController extends Controller
{
    /**
     * Summary/chart key => permission slug required to see it. A key
     * missing from the response means "this user isn't authorized for it,"
     * not "no data" — the frontend renders that as an omitted card/chart,
     * never a fabricated zero.
     */
    private const SUMMARY_PERMISSIONS = [
        'total_students' => 'students.read',
        'active_students' => 'students.read',
        'total_lecturers' => 'lecturers.read',
        'total_employees' => 'employees.read',
        'total_study_programs' => 'study_programs.read',
        'active_classes' => 'classes.read',
        'unpaid_invoices' => 'invoices.read',
    ];

    private const CHART_PERMISSIONS = [
        'student_growth' => 'students.read',
        'student_status' => 'students.read',
        'students_by_program' => 'students.read',
        'staff_by_unit' => ['lecturers.read', 'employees.read'],
        'payment_trend' => 'invoices.read',
        'invoice_status' => 'invoices.read',
        'active_classes_by_program' => 'classes.read',
        'approval_status' => 'approval_requests.read',
    ];

    public function __construct(private readonly DashboardStatsService $stats, private readonly TenantContext $tenant) {}

    public function index(Request $request): JsonResponse
    {
        if ($this->tenant->universityId() === null) {
            return ApiResponse::error('Pilih universitas terlebih dahulu.');
        }

        $filters = [
            'academic_term_id' => $request->query('academic_term_id'),
            'faculty_id' => $request->query('faculty_id'),
            'study_program_id' => $request->query('study_program_id'),
            'date_from' => $request->query('date_from'),
            'date_to' => $request->query('date_to'),
        ];

        $result = $this->stats->compute($filters);
        $result['summary']['pending_approvals'] = $this->pendingApprovals($request);

        $user = $request->user();

        $result['summary'] = array_filter(
            $result['summary'],
            fn ($value, $key) => $key === 'pending_approvals' || $user->hasPermissionTo(self::SUMMARY_PERMISSIONS[$key]),
            ARRAY_FILTER_USE_BOTH,
        );

        $result['charts'] = array_filter(
            $result['charts'],
            function ($value, $key) use ($user) {
                $required = self::CHART_PERMISSIONS[$key];
                $requiredList = is_array($required) ? $required : [$required];

                foreach ($requiredList as $permission) {
                    if ($user->hasPermissionTo($permission)) {
                        return true;
                    }
                }

                return false;
            },
            ARRAY_FILTER_USE_BOTH,
        );

        return ApiResponse::success($result);
    }

    /**
     * Same semantics as ApprovalRequestController::index() — tenant-wide
     * count for users with approval_requests.read, otherwise just their own
     * submissions. Deliberately not part of DashboardStatsService's cached
     * blob since the result depends on which user is asking.
     */
    private function pendingApprovals(Request $request): int
    {
        $query = ApprovalRequest::query()->whereIn('status', [
            ApprovalRequestStatus::Submitted,
            ApprovalRequestStatus::InProgress,
        ]);

        if (! $request->user()->hasPermissionTo('approval_requests.read')) {
            $query->where('requested_by', $request->user()->id);
        }

        return $query->count();
    }
}
