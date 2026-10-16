<?php

namespace Modules\Finance\Controllers;

use App\Support\Http\ApiResponse;
use App\Support\Http\ListQuery;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Academic\Models\AcademicTerm;
use Modules\Academic\Models\Student;
use Modules\AuditLog\Enums\AuditAction;
use Modules\AuditLog\Services\AuditLogService;
use Modules\Finance\Enums\BillGroup;
use Modules\Finance\Enums\InvoiceLineType;
use Modules\Finance\Enums\InvoiceStatus;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Services\InvoiceWriter;
use Modules\Finance\Support\Money;
use Modules\Finance\Support\Presenter;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Tagihan mahasiswa (F-03). `GET /invoices` & `GET /invoices/{id}` sudah
 * ada sebelum Modul KEU (dipakai dashboard) — parameter `filter[...]` lama
 * tetap didukung, kini ditambah pencarian NIM/nama/nomor tagihan.
 */
class InvoiceController extends KeuController
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Invoice::class);

        $query = Invoice::query()->with('student.studyProgram')->latest('due_date')
            ->when($request->filled('search'), function ($q) use ($request): void {
                $term = '%'.$request->string('search').'%';
                $q->where(fn ($w) => $w->where('invoice_number', 'like', $term)->orWhere('period', 'like', $term)
                    ->orWhereIn('student_id', Student::query()->select('id')->where(fn ($s) => $s->where('nim', 'like', $term)->orWhere('name', 'like', $term))));
            })
            ->when($request->boolean('overdue'), fn ($q) => $q->where('is_overdue', true)->whereIn('status', InvoiceStatus::open()))
            ->when($request->filled('study_program_id'), fn ($q) => $q->whereIn('student_id', Student::query()->select('id')->where('study_program_id', $request->string('study_program_id'))))
            ->when($request->filled('admission_year'), fn ($q) => $q->whereIn('student_id', Student::query()->select('id')->where('admission_year', $request->integer('admission_year'))))
            ->when($request->filled('due_from'), fn ($q) => $q->whereDate('due_date', '>=', $request->string('due_from')))
            ->when($request->filled('due_to'), fn ($q) => $q->whereDate('due_date', '<=', $request->string('due_to')));

        $paginator = ListQuery::paginate(
            query: $query,
            request: $request,
            filterable: ['status', 'student_id', 'academic_term_id', 'bill_group'],
            sortable: ['due_date', 'amount', 'created_at', 'invoice_number'],
        );

        return ApiResponse::success(
            $paginator->getCollection()->map(fn (Invoice $i) => Presenter::invoice($i))->values(),
            meta: ['current_page' => $paginator->currentPage(), 'per_page' => $paginator->perPage(), 'total' => $paginator->total(), 'last_page' => $paginator->lastPage()],
        );
    }

    public function show(Invoice $invoice): JsonResponse
    {
        $this->authorize('view', $invoice);
        $invoice->load(['student.studyProgram', 'items', 'installments', 'payments', 'adjustments.requester', 'adjustments.decider', 'penaltyInvoices', 'academicTerm']);

        return ApiResponse::success(Presenter::invoice($invoice, detail: true));
    }

    /** Tagihan manual/insidental (FR-TAG-04): draf atau langsung terbit. */
    public function store(Request $request, InvoiceWriter $writer, AuditLogService $audit): JsonResponse
    {
        $data = $request->validate([
            'student_id' => ['required', 'string', Rule::exists('students', 'id')],
            'academic_term_id' => ['nullable', 'string', Rule::exists('academic_terms', 'id')],
            'bill_group' => ['required', Rule::enum(BillGroup::class), Rule::notIn([BillGroup::Penalty->value, BillGroup::Admission->value])],
            'due_date' => ['required', 'date', 'after_or_equal:today'],
            'period' => ['nullable', 'string', 'max:60'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'draft' => ['boolean'],
            'lines' => ['required', 'array', 'min:1', 'max:30'],
            'lines.*.fee_component_id' => ['nullable', 'string', Rule::exists('fee_components', 'id')],
            'lines.*.description' => ['required', 'string', 'max:255'],
            'lines.*.amount' => ['required', 'numeric', 'gt:0', 'max:999999999999'],
        ]);

        $student = Student::query()->findOrFail($data['student_id']);
        $term = isset($data['academic_term_id']) ? AcademicTerm::query()->find($data['academic_term_id']) : null;
        $lines = array_map(fn (array $line) => [
            'line_type' => InvoiceLineType::Charge,
            'description' => $line['description'],
            'amount_cents' => Money::toCents((string) $line['amount']),
            'fee_component_id' => $line['fee_component_id'] ?? null,
            'source_type' => 'manual',
        ], $data['lines']);

        $invoice = $writer->create($student, $term, BillGroup::from($data['bill_group']), $lines, CarbonImmutable::parse($data['due_date']), $this->user($request), (bool) ($data['draft'] ?? false), array_filter([
            'period' => $data['period'] ?? null,
            'notes' => $data['notes'] ?? null,
            'source_type' => 'manual',
        ]));
        $audit->recordAction($invoice, AuditAction::Created, ['invoice_number' => $invoice->invoice_number, 'amount' => (string) $invoice->amount]);

        return ApiResponse::success(Presenter::invoice($invoice->load('student')), $invoice->status === InvoiceStatus::Draft ? 'Draf tagihan disimpan.' : 'Tagihan diterbitkan.', status: 201);
    }

    public function issue(Invoice $invoice, InvoiceWriter $writer): JsonResponse
    {
        if ($invoice->status !== InvoiceStatus::Draft) {
            abort(409, 'Hanya draf tagihan yang dapat diterbitkan.');
        }

        return ApiResponse::success(Presenter::invoice($writer->issueDraft($invoice)), 'Tagihan diterbitkan.');
    }

    public function destroyDraft(Invoice $invoice): JsonResponse
    {
        if ($invoice->status !== InvoiceStatus::Draft) {
            abort(409, 'Hanya draf tagihan yang dapat dihapus; tagihan terbit dibatalkan lewat penyesuaian.');
        }

        $invoice->forceFill(['status' => InvoiceStatus::Cancelled, 'cancelled_at' => now(), 'cancel_reason' => 'Draf dihapus'])->save();

        return ApiResponse::success(null, 'Draf tagihan dibatalkan.');
    }

    /** Ekspor CSV daftar tagihan sesuai filter (BR-KEU-73: alasan wajib & tercatat). */
    public function export(Request $request, AuditLogService $audit): StreamedResponse
    {
        $request->validate(['reason' => ['required', 'string', 'min:10', 'max:255']]);
        $query = Invoice::query()->with('student')->orderBy('due_date')
            ->when($request->filled('status'), fn ($q) => $q->whereIn('status', explode(',', $request->string('status'))))
            ->when($request->filled('academic_term_id'), fn ($q) => $q->where('academic_term_id', $request->string('academic_term_id')))
            ->when($request->filled('bill_group'), fn ($q) => $q->where('bill_group', $request->string('bill_group')));
        $audit->log('finance', 'Ekspor daftar tagihan', null, ['filters' => $request->except('reason'), 'reason' => $request->string('reason')->toString()]);

        return response()->streamDownload(function () use ($query): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['No. Tagihan', 'NIM', 'Nama', 'Periode', 'Kelompok', 'Bruto', 'Potongan', 'Neto', 'Dibayar', 'Sisa', 'Status', 'Jatuh Tempo']);
            $query->chunk(500, function ($invoices) use ($out): void {
                foreach ($invoices as $i) {
                    fputcsv($out, array_map([CsvSafe::class, 'cell'], [$i->invoice_number, $i->student?->nim, $i->student?->name, $i->period, $i->bill_group->label(),
                        (string) $i->gross_amount, (string) $i->discount_amount, (string) $i->amount, (string) $i->paid_amount, $i->outstanding(), $i->status->label(), $i->due_date->toDateString()]));
                }
            });
            fclose($out);
        }, 'tagihan-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv']);
    }
}
