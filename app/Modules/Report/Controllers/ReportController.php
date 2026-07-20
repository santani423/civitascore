<?php

namespace Modules\Report\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Report\Services\ReportService;

/**
 * No Policy/Eloquent model — a report is a query, not a persisted entity, so
 * there's nothing for a per-record `$this->authorize()` call to target. The
 * `permission:reports.read` route middleware is the whole access gate here.
 */
class ReportController extends Controller
{
    private const PREVIEW_LIMIT = 200;

    public function __construct(private readonly ReportService $reportService) {}

    public function index(): JsonResponse
    {
        $types = collect(ReportService::REPORT_TYPES)
            ->map(fn (string $label, string $key) => ['type' => $key, 'label' => $label])
            ->values();

        return ApiResponse::success($types);
    }

    public function show(string $type, Request $request): JsonResponse|Response
    {
        abort_unless(array_key_exists($type, ReportService::REPORT_TYPES), 404);

        $report = $this->reportService->build($type);

        if ($request->query('export') === 'csv') {
            return $this->streamCsv($type, $report);
        }

        $rows = $report['rows'];

        return ApiResponse::success([
            'type' => $type,
            'label' => ReportService::REPORT_TYPES[$type],
            'columns' => $report['columns'],
            'summary' => $report['summary'],
            'rows' => array_slice($rows, 0, self::PREVIEW_LIMIT),
            'total_rows' => count($rows),
            'truncated' => count($rows) > self::PREVIEW_LIMIT,
        ]);
    }

    /**
     * @param  array{columns: array<int, array{key: string, label: string}>, rows: array<int, array<string, mixed>>, summary: array<string, mixed>}  $report
     */
    private function streamCsv(string $type, array $report): Response
    {
        $filename = "laporan-{$type}-".now()->format('Ymd-His').'.csv';

        $csv = fopen('php://temp', 'w+');
        fputcsv($csv, array_column($report['columns'], 'label'));

        foreach ($report['rows'] as $row) {
            fputcsv($csv, array_values($row));
        }

        rewind($csv);
        $contents = stream_get_contents($csv);
        fclose($csv);

        return response($contents, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
