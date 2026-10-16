<?php

namespace Modules\Finance\Controllers\Admin;

use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\AuditLog\Services\AuditLogService;
use Modules\Finance\Controllers\CsvSafe;
use Modules\Finance\Controllers\KeuController;
use Modules\Finance\Services\FinanceReportService;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Laporan KEU (rancangan §11.3) + ekspor CSV bertanda alasan (BR-KEU-73). */
class ReportController extends KeuController
{
    public function __construct(private readonly FinanceReportService $reports) {}

    public function show(Request $request, string $type): JsonResponse
    {
        abort_unless(isset(FinanceReportService::TYPES[$type]), 404);

        return ApiResponse::success($this->reports->build($type, $this->filters($request), 500));
    }

    public function export(Request $request, string $type, AuditLogService $audit): StreamedResponse
    {
        abort_unless(isset(FinanceReportService::TYPES[$type]), 404);
        $request->validate(['reason' => ['required', 'string', 'min:10', 'max:255'], 'format' => ['nullable', Rule::in(['csv'])]]);
        $report = $this->reports->build($type, $this->filters($request), null);
        $audit->log('finance', 'Ekspor laporan '.$report['title'], null, ['type' => $type, 'filters' => $this->filters($request), 'rows' => $report['total_rows'], 'reason' => $request->string('reason')->toString()]);

        return response()->streamDownload(function () use ($report): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, array_column($report['columns'], 'label'));

            foreach ($report['rows'] as $row) {
                fputcsv($out, array_map(fn ($c) => CsvSafe::cell(is_array($row[$c['key']] ?? null) ? json_encode($row[$c['key']]) : ($row[$c['key']] ?? '')), $report['columns']));
            }

            fclose($out);
        }, $type.'-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
