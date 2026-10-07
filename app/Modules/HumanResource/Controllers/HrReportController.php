<?php

namespace Modules\HumanResource\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\HumanResource\Services\HrExportService;
use Modules\HumanResource\Services\HrReportService;
use Symfony\Component\HttpFoundation\Response;

class HrReportController extends Controller
{
    public function __construct(
        private readonly HrReportService $reports,
        private readonly HrExportService $exports,
    ) {}

    public function index(): JsonResponse
    {
        return ApiResponse::success(collect(HrReportService::REPORT_TYPES)
            ->map(fn (string $label, string $type): array => ['type' => $type, 'label' => $label])
            ->values());
    }

    /**
     * Tanpa `format` → JSON untuk ditampilkan; `format=xlsx|pdf` → unduhan
     * (butuh hr_reports.export, dicek di sini karena route-nya sama).
     */
    public function show(Request $request, string $type): JsonResponse|Response
    {
        abort_unless(array_key_exists($type, HrReportService::REPORT_TYPES), 404);

        $filters = $request->validate([
            'format' => ['nullable', Rule::in(['xlsx', 'pdf'])],
            'employee_type' => ['nullable', Rule::in(['lecturer', 'staff'])],
            'is_active' => ['nullable', Rule::in(['true', 'false', '1', '0'])],
            'work_unit_id' => ['nullable', 'string'],
            'employment_status' => ['nullable', 'string'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $report = $this->reports->build($type, $filters);
        $title = HrReportService::REPORT_TYPES[$type];

        if (! empty($filters['format'])) {
            abort_unless($request->user()?->hasPermissionTo('hr_reports.export'), 403, 'Anda tidak memiliki izin untuk mengekspor laporan.');

            return $this->exports->download($filters['format'], $title, $report);
        }

        return ApiResponse::success(['type' => $type, 'label' => $title, ...$report]);
    }
}
