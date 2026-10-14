<?php

namespace Modules\Admission\Controllers\Admin;

use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Admission\Enums\ExportStatus;
use Modules\Admission\Models\ExportLog;
use Modules\Admission\Services\Reporting\ReportService;
use Modules\Admission\Support\PmbFileStorage;
use Modules\FileManagement\Models\FileUpload;

/** Dasbor KPI, laporan, ekspor (FR-RPT, 11 §A.15, ADM-01/80..86). */
class ReportController extends AdminController
{
    private const FILTER_RULES = [
        'period_id' => ['nullable', 'string', 'size:26'],
        'wave_id' => ['nullable', 'string', 'size:26'],
        'admission_path_id' => ['nullable', 'string', 'size:26'],
        'study_program_id' => ['nullable', 'string'],
        'study_system_id' => ['nullable', 'string', 'size:26'],
        'faculty_id' => ['nullable', 'string'],
        'choice' => ['nullable', 'in:first,any'],
        'payment_status' => ['nullable', 'string', 'max:30'],
        'from' => ['nullable', 'date'],
        'to' => ['nullable', 'date'],
    ];

    public function __construct(private readonly ReportService $reports) {}

    public function summary(Request $request): JsonResponse
    {
        return ApiResponse::success($this->reports->summary(array_filter($request->validate(self::FILTER_RULES)), $this->user($request)));
    }

    public function trend(Request $request): JsonResponse
    {
        $data = $request->validate([
            ...self::FILTER_RULES,
            'metrics' => ['nullable', 'array'],
            'metrics.*' => ['in:accounts,submitted,paid,enrolled'],
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
        ]);
        $filters = array_filter(collect($data)->except(['metrics', 'from', 'to'])->all());

        return ApiResponse::success($this->reports->trend($filters, $data['metrics'] ?? ['accounts', 'submitted', 'paid', 'enrolled'], $data['from'], $data['to'], $this->user($request)));
    }

    public function comparison(Request $request): JsonResponse
    {
        $data = $request->validate([
            'period_ids' => ['required', 'array', 'min:1', 'max:5'],
            'period_ids.*' => ['string', 'size:26'],
            'metric' => ['required', 'string', 'max:50'],
            'max_day' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        return ApiResponse::success($this->reports->comparison($data['period_ids'], $data['metric'], (int) ($data['max_day'] ?? 120)));
    }

    public function report(Request $request, string $code): JsonResponse
    {
        $filters = array_filter($request->validate(self::FILTER_RULES));
        $report = $this->reports->report($code, $filters, $this->user($request));
        $perPage = max(1, min(200, $request->integer('per_page', 50)));
        $page = max(1, $request->integer('page', 1));
        $total = $report['rows']->count();

        return ApiResponse::success([
            'title' => $report['title'],
            'columns' => $report['columns'],
            'rows' => $report['rows']->forPage($page, $perPage)->values(),
            'available' => collect(ReportService::REPORTS)->filter(fn ($def) => $def[1] === null || $this->user($request)->hasPermissionTo($def[1]))->map(fn ($def, $key) => ['code' => $key, 'title' => $def[0]])->values(),
        ], meta: ['current_page' => $page, 'per_page' => $perPage, 'total' => $total, 'last_page' => max(1, (int) ceil($total / $perPage))]);
    }

    public function requestExport(Request $request, string $code): JsonResponse
    {
        $data = $request->validate([
            'format' => ['required', Rule::in(['xlsx', 'csv', 'pdf'])],
            'filters' => ['nullable', 'array'],
            'columns' => ['nullable', 'array'],
            'columns.*' => ['string', 'max:60'],
            'reason' => ['required', 'string', 'min:5', 'max:255'],
        ]);
        $filters = array_filter(validator((array) ($data['filters'] ?? []), self::FILTER_RULES)->validate());
        $log = $this->reports->requestExport($code, $data['format'], $filters, $data['columns'] ?? [], $data['reason'], $this->user($request));

        return ApiResponse::success($this->presentExport($log), 'Ekspor sedang diproses; tautan unduhan tersedia di menu Ekspor Saya.', status: 202);
    }

    public function myExports(Request $request): JsonResponse
    {
        return $this->paginate(ExportLog::query()->where('user_id', $this->user($request)->getKey())->orderByDesc('created_at'), $request, fn (ExportLog $l) => $this->presentExport($l));
    }

    /** Unduhan hanya oleh pemilik ekspor, selama belum kedaluwarsa (M.4). */
    public function export(Request $request, ExportLog $exportLog, PmbFileStorage $storage): JsonResponse
    {
        abort_unless((string) $exportLog->user_id === (string) $this->user($request)->getKey(), 404);

        if ($exportLog->status !== ExportStatus::Ready || $exportLog->file_upload_id === null || ($exportLog->expires_at !== null && $exportLog->expires_at->isPast())) {
            return ApiResponse::success($this->presentExport($exportLog));
        }

        return ApiResponse::success([...$this->presentExport($exportLog), 'url' => $storage->temporaryUrl(FileUpload::query()->findOrFail($exportLog->file_upload_id))])->header('Cache-Control', 'no-store');
    }

    /** @return array<string, mixed> */
    private function presentExport(ExportLog $l): array
    {
        $expired = $l->expires_at !== null && $l->expires_at->isPast();

        return [
            'id' => $l->getKey(), 'report_code' => $l->report_code, 'title' => ReportService::REPORTS[$l->report_code][0] ?? $l->report_code,
            'format' => $l->format, 'status' => $expired ? ExportStatus::Expired->value : $l->status->value, 'row_count' => $l->row_count,
            'reason' => $l->reason, 'contains_personal_data' => $l->contains_personal_data, 'error_message' => $l->status === ExportStatus::Failed ? 'Ekspor gagal diproses.' : null,
            'expires_at' => $l->expires_at?->toIso8601String(), 'created_at' => $l->created_at?->toIso8601String(),
        ];
    }
}
