<?php

namespace Modules\Finance\Controllers\Admin;

use App\Support\Http\ApiResponse;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Academic\Models\AcademicTerm;
use Modules\Academic\Models\Student;
use Modules\Finance\Controllers\KeuController;
use Modules\Finance\Enums\AdjustmentType;
use Modules\Finance\Enums\ApprovalStatus;
use Modules\Finance\Enums\BillGroup;
use Modules\Finance\Models\BillingRun;
use Modules\Finance\Models\BillingRunItem;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\InvoiceAdjustment;
use Modules\Finance\Services\AdjustmentService;
use Modules\Finance\Services\BillingService;
use Modules\Finance\Support\Presenter;

/** Penerbitan tagihan massal, tagihan SKS, dan penyesuaian tagihan (F-03, KD-KEU-04). */
class BillingController extends KeuController
{
    public function __construct(private readonly BillingService $billing, private readonly AdjustmentService $adjustments) {}

    public function runs(Request $request): JsonResponse
    {
        $query = BillingRun::query()->with(['academicTerm', 'creator', 'approver'])->latest()
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')));

        return $this->paginate($query, $request, fn (BillingRun $r) => $this->presentRun($r));
    }

    public function showRun(Request $request, BillingRun $billingRun): JsonResponse
    {
        $items = BillingRunItem::query()->where('billing_run_id', $billingRun->id)->with('student')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderBy('status');

        return ApiResponse::success([
            ...$this->presentRun($billingRun->load(['academicTerm', 'creator', 'approver'])),
            'items' => $items->limit(1000)->get()->map(fn (BillingRunItem $i) => [
                'id' => $i->id, 'student' => Presenter::student($i->student), 'status' => $i->status, 'skip_reason' => $i->skip_reason, 'error_message' => $i->error_message,
                'gross' => (string) $i->gross_amount, 'discount' => (string) $i->discount_amount, 'net' => (string) $i->net_amount, 'invoice_id' => $i->invoice_id, 'lines' => $i->lines,
            ]),
        ]);
    }

    public function simulate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'academic_term_id' => ['required', 'string', Rule::exists('academic_terms', 'id')],
            'bill_group' => ['required', Rule::in([BillGroup::Registration->value, BillGroup::Incidental->value, BillGroup::Graduation->value])],
            'fee_component_ids' => ['required', 'array', 'min:1'],
            'fee_component_ids.*' => ['string'],
            'due_date' => ['required', 'date'],
            'study_program_ids' => ['nullable', 'array'],
            'admission_years' => ['nullable', 'array'],
            'admission_years.*' => ['integer'],
            'student_ids' => ['nullable', 'array'],
        ]);

        $run = $this->billing->simulate(
            AcademicTerm::query()->findOrFail($data['academic_term_id']),
            BillGroup::from($data['bill_group']),
            $data['fee_component_ids'],
            array_filter(['study_program_ids' => $data['study_program_ids'] ?? null, 'admission_years' => $data['admission_years'] ?? null, 'student_ids' => $data['student_ids'] ?? null]),
            CarbonImmutable::parse($data['due_date']),
            $this->user($request),
        );

        return ApiResponse::success($this->presentRun($run->load('academicTerm')), 'Simulasi penerbitan tagihan selesai.', status: 201);
    }

    public function submit(Request $request, BillingRun $billingRun): JsonResponse
    {
        return ApiResponse::success($this->presentRun($this->billing->submit($billingRun, $this->user($request))), 'Diajukan untuk persetujuan.');
    }

    public function approve(Request $request, BillingRun $billingRun): JsonResponse
    {
        $run = $this->billing->approve($billingRun, $this->user($request));

        return ApiResponse::success($this->presentRun($run), 'Tagihan diterbitkan: '.($run->posted_totals['invoices'] ?? 0).' tagihan.');
    }

    public function reject(Request $request, BillingRun $billingRun): JsonResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:255']]);

        return ApiResponse::success($this->presentRun($this->billing->reject($billingRun, $this->user($request), $data['reason'])), 'Penerbitan ditolak.');
    }

    public function cancel(BillingRun $billingRun): JsonResponse
    {
        return ApiResponse::success($this->presentRun($this->billing->cancel($billingRun)), 'Penerbitan dibatalkan.');
    }

    /** Hitung/terbitkan ulang tagihan biaya SKS seorang mahasiswa (FR-TAG-02/03). */
    public function billCredits(Request $request): JsonResponse
    {
        $data = $request->validate(['student_id' => ['required', 'string'], 'academic_term_id' => ['required', 'string']]);
        $invoice = $this->billing->billCredits(Student::query()->findOrFail($data['student_id']), AcademicTerm::query()->findOrFail($data['academic_term_id']), $this->user($request));

        return ApiResponse::success($invoice ? Presenter::invoice($invoice) : null, $invoice ? 'Tagihan SKS diperbarui.' : 'Tidak ada tagihan SKS (tarif per SKS belum ada atau SKS nol).');
    }

    public function adjustments(Request $request): JsonResponse
    {
        $query = InvoiceAdjustment::query()->with(['invoice.student', 'requester', 'decider'])->latest()
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('invoice_id'), fn ($q) => $q->where('invoice_id', $request->string('invoice_id')));

        return $this->paginate($query, $request, fn (InvoiceAdjustment $a) => Presenter::adjustment($a));
    }

    public function requestAdjustment(Request $request, Invoice $invoice): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in([AdjustmentType::Increase->value, AdjustmentType::Decrease->value, AdjustmentType::Waiver->value, AdjustmentType::PenaltyWaiver->value, AdjustmentType::Void->value])],
            'amount' => ['required_unless:type,void', 'nullable', 'numeric', 'gt:0'],
            'reason_code' => ['required', 'string', 'max:40'],
            'reason_note' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        $adjustment = $this->adjustments->request($invoice, AdjustmentType::from($data['type']), (string) ($data['amount'] ?? '0'), $data['reason_code'], $data['reason_note'], $this->user($request));

        return ApiResponse::success(Presenter::adjustment($adjustment), 'Penyesuaian diajukan dan menunggu persetujuan.', status: 201);
    }

    public function approveAdjustment(Request $request, InvoiceAdjustment $invoiceAdjustment): JsonResponse
    {
        $data = $request->validate(['note' => ['nullable', 'string', 'max:1000']]);

        return ApiResponse::success(Presenter::adjustment($this->adjustments->approve($invoiceAdjustment, $this->user($request), $data['note'] ?? null)), 'Penyesuaian disetujui dan diterapkan.');
    }

    public function rejectAdjustment(Request $request, InvoiceAdjustment $invoiceAdjustment): JsonResponse
    {
        $data = $request->validate(['note' => ['required', 'string', 'min:10', 'max:1000']]);

        return ApiResponse::success(Presenter::adjustment($this->adjustments->reject($invoiceAdjustment, $this->user($request), $data['note'])), 'Penyesuaian ditolak.');
    }

    public function cancelAdjustment(Request $request, InvoiceAdjustment $invoiceAdjustment): JsonResponse
    {
        abort_unless($invoiceAdjustment->status === ApprovalStatus::PendingApproval && $invoiceAdjustment->requested_by === $this->user($request)->id, 409, 'Hanya pengaju yang dapat membatalkan penyesuaian yang belum diputuskan.');
        $invoiceAdjustment->forceFill(['status' => ApprovalStatus::Cancelled])->save();

        return ApiResponse::success(Presenter::adjustment($invoiceAdjustment), 'Penyesuaian dibatalkan.');
    }

    /** @return array<string, mixed> */
    private function presentRun(BillingRun $run): array
    {
        return [
            'id' => $run->id, 'run_number' => $run->run_number, 'academic_term_id' => $run->academic_term_id,
            'academic_term' => $run->relationLoaded('academicTerm') ? $run->academicTerm?->label() : null,
            'bill_group' => $run->bill_group->value, 'bill_group_label' => $run->bill_group->label(), 'fee_component_ids' => $run->fee_component_ids, 'scope' => $run->scope,
            'due_date' => $run->due_date->toDateString(), 'status' => $run->status->value, 'status_label' => $run->status->label(),
            'totals' => $run->totals, 'posted_totals' => $run->posted_totals, 'rejection_reason' => $run->rejection_reason,
            'created_by' => $run->relationLoaded('creator') ? $run->creator?->name : $run->created_by, 'created_by_id' => $run->created_by,
            'approved_by' => $run->relationLoaded('approver') ? $run->approver?->name : $run->approved_by,
            'created_at' => $run->created_at?->toIso8601String(), 'posted_at' => $run->posted_at?->toIso8601String(),
        ];
    }
}
