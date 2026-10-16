<?php

namespace Modules\Finance\Controllers\Admin;

use App\Support\Http\ApiResponse;
use App\Support\Http\Exceptions\ConflictException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Modules\Academic\Models\Student;
use Modules\FileManagement\Models\FileUpload;
use Modules\FileManagement\Support\FileAttacher;
use Modules\Finance\Controllers\KeuController;
use Modules\Finance\Models\ReconciliationBatch;
use Modules\Finance\Models\ReconciliationItem;
use Modules\Finance\Services\ReconciliationService;
use Modules\Finance\Support\Presenter;

/** Rekonsiliasi mutasi bank / settlement / setoran kas (F-12). */
class ReconciliationController extends KeuController
{
    public function __construct(private readonly ReconciliationService $service) {}

    public function index(Request $request): JsonResponse
    {
        $query = ReconciliationBatch::query()->with(['creator', 'closer', 'channel'])->latest('statement_date')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')));

        return $this->paginate($query, $request, fn (ReconciliationBatch $b) => $this->present($b));
    }

    public function show(ReconciliationBatch $reconciliationBatch): JsonResponse
    {
        $reconciliationBatch->load(['creator', 'closer', 'channel', 'items.transaction.student', 'items.resolver']);

        return ApiResponse::success([
            ...$this->present($reconciliationBatch),
            'items' => $reconciliationBatch->items->sortBy(fn (ReconciliationItem $i) => [$i->result->value === 'matched' ? 1 : 0, $i->external_date])->values()->map(fn (ReconciliationItem $i) => [
                'id' => $i->id, 'external_reference' => $i->external_reference, 'external_amount' => $i->external_amount !== null ? (string) $i->external_amount : null,
                'external_date' => $i->external_date?->toDateString(), 'external_description' => $i->external_description,
                'result' => $i->result->value, 'result_label' => $i->result->label(), 'resolution' => $i->resolution,
                'resolution_label' => $i->resolution ? (ReconciliationService::RESOLUTIONS[$i->resolution] ?? $i->resolution) : null,
                'resolution_note' => $i->resolution_note, 'resolved_by' => $i->resolver?->name,
                'transaction' => $i->transaction ? Presenter::transaction($i->transaction) : null,
            ]),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'source' => ['required', Rule::in(['bank_statement', 'provider_settlement', 'cash_deposit'])],
            'statement_date' => ['required', 'date', 'before_or_equal:today'],
            'payment_channel_id' => ['nullable', 'string', Rule::exists('payment_channels', 'id')],
        ]);

        return ApiResponse::success($this->present($this->service->createBatch($data['source'], $data['statement_date'], $data['payment_channel_id'] ?? null, $this->user($request))), 'Batch rekonsiliasi dibuat.', status: 201);
    }

    /**
     * Impor baris mutasi: JSON `rows` atau berkas CSV (kolom: tanggal,
     * keterangan, nominal, referensi) yang sudah diunggah ke /file-uploads.
     */
    public function import(Request $request, ReconciliationBatch $reconciliationBatch, FileAttacher $files): JsonResponse
    {
        $data = $request->validate([
            'rows' => ['nullable', 'array', 'max:5000'],
            'rows.*.date' => ['required_with:rows', 'date'],
            'rows.*.amount' => ['required_with:rows', 'numeric'],
            'rows.*.description' => ['nullable', 'string', 'max:255'],
            'rows.*.reference' => ['nullable', 'string', 'max:100'],
            'file_upload_id' => ['nullable', 'string'],
            'flag_missing' => ['boolean'],
        ]);

        $rows = $data['rows'] ?? [];

        if (! empty($data['file_upload_id'])) {
            $file = $files->resolve($data['file_upload_id'], 'file_upload_id', $this->user($request), null, ['csv', 'txt'], 5);
            $rows = $this->parseCsv($file);
            $reconciliationBatch->forceFill(['source_file_id' => $file->id])->save();
            $files->attach($file, $reconciliationBatch);
        }

        if ($rows === []) {
            throw new ConflictException('Tidak ada baris mutasi untuk diimpor.');
        }

        $batch = $this->service->import($reconciliationBatch, $rows, (bool) ($data['flag_missing'] ?? false));

        return ApiResponse::success($this->present($batch), "Impor selesai: {$batch->matched_items} cocok, {$batch->exception_items} pengecualian.");
    }

    public function resolve(Request $request, ReconciliationItem $reconciliationItem): JsonResponse
    {
        $data = $request->validate([
            'resolution' => ['required', Rule::in(array_keys(ReconciliationService::RESOLUTIONS))],
            'note' => ['required', 'string', 'min:5', 'max:1000'],
            'student_id' => ['nullable', 'string'],
            'transaction_id' => ['nullable', 'string'],
        ]);
        $student = isset($data['student_id']) ? Student::query()->findOrFail($data['student_id']) : null;
        $item = $this->service->resolve($reconciliationItem, $data['resolution'], $data['note'], $this->user($request), $student, $data['transaction_id'] ?? null);

        return ApiResponse::success(['id' => $item->id, 'resolution' => $item->resolution], 'Baris rekonsiliasi diselesaikan.');
    }

    public function close(Request $request, ReconciliationBatch $reconciliationBatch): JsonResponse
    {
        return ApiResponse::success($this->present($this->service->close($reconciliationBatch, $this->user($request))), 'Batch rekonsiliasi ditutup.');
    }

    /** @return array<int, array{date: string, description: ?string, amount: string, reference: ?string}> */
    private function parseCsv(FileUpload $file): array
    {
        $content = Storage::disk($file->disk)->get($file->path) ?? '';
        $lines = preg_split('/\r\n|\n|\r/', trim($content)) ?: [];
        $rows = [];

        foreach ($lines as $index => $line) {
            $cells = str_getcsv($line, str_contains($line, ';') ? ';' : ',');

            if ($index === 0 && ! preg_match('/\d/', $cells[0] ?? '')) {
                continue;
            }

            if (count($cells) < 3) {
                continue;
            }

            $rows[] = ['date' => trim((string) $cells[0]), 'description' => trim((string) ($cells[1] ?? '')), 'amount' => self::amount((string) $cells[2]), 'reference' => trim((string) ($cells[3] ?? '')) ?: null];
        }

        return $rows;
    }

    /** Nominal mutasi format Indonesia (1.400.000,50) maupun internasional (1,400,000.50 / 1400000.50). */
    public static function amount(string $cell): string
    {
        $raw = preg_replace('/[^0-9.,\-]/', '', $cell) ?? '';

        if (str_contains($raw, ',') && str_contains($raw, '.')) {
            $raw = strrpos($raw, ',') > strrpos($raw, '.') ? str_replace(',', '.', str_replace('.', '', $raw)) : str_replace(',', '', $raw);
        } elseif (str_contains($raw, ',')) {
            $raw = preg_match('/,\d{1,2}$/', $raw) ? str_replace(',', '.', $raw) : str_replace(',', '', $raw);
        } elseif (substr_count($raw, '.') > 1 || preg_match('/\.\d{3}$/', $raw)) {
            $raw = str_replace('.', '', $raw);
        }

        return $raw === '' ? '0' : $raw;
    }

    /** @return array<string, mixed> */
    private function present(ReconciliationBatch $b): array
    {
        return [
            'id' => $b->id, 'batch_number' => $b->batch_number, 'source' => $b->source, 'statement_date' => $b->statement_date?->toDateString(),
            'channel' => $b->relationLoaded('channel') ? $b->channel?->name : null, 'status' => $b->status->value, 'status_label' => $b->status->label(),
            'total_items' => $b->total_items, 'matched_items' => $b->matched_items, 'exception_items' => $b->exception_items, 'total_amount' => (string) $b->total_amount,
            'created_by' => $b->relationLoaded('creator') ? $b->creator?->name : null, 'closed_by' => $b->relationLoaded('closer') ? $b->closer?->name : null,
            'closed_at' => $b->closed_at?->toIso8601String(),
        ];
    }
}
