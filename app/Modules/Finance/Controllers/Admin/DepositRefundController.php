<?php

namespace Modules\Finance\Controllers\Admin;

use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Academic\Models\Student;
use Modules\AuditLog\Enums\AuditAction;
use Modules\AuditLog\Services\AuditLogService;
use Modules\FileManagement\Support\FileAttacher;
use Modules\Finance\Controllers\KeuController;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\InvoiceInstallment;
use Modules\Finance\Models\Refund;
use Modules\Finance\Models\StudentDepositAccount;
use Modules\Finance\Models\StudentDepositEntry;
use Modules\Finance\Services\PaymentPostingService;
use Modules\Finance\Services\RefundService;
use Modules\Finance\Support\Money;
use Modules\Finance\Support\Presenter;

/** Deposit (F-11) & pengembalian dana (F-13). */
class DepositRefundController extends KeuController
{
    public function accounts(Request $request): JsonResponse
    {
        $query = StudentDepositAccount::query()->with('student.studyProgram')->orderByDesc('balance')
            ->when(! $request->boolean('include_empty'), fn ($q) => $q->where(fn ($w) => $w->where('balance', '>', 0)->orWhere('held_amount', '>', 0)))
            ->when($request->filled('search'), fn ($q) => $q->whereIn('student_id', Student::query()->select('id')->where(fn ($s) => $s->where('nim', 'like', '%'.$request->string('search').'%')->orWhere('name', 'like', '%'.$request->string('search').'%'))));

        return $this->paginate($query, $request, fn (StudentDepositAccount $a) => [
            'id' => $a->id, 'student' => Presenter::student($a->student), 'balance' => (string) $a->balance, 'held_amount' => (string) $a->held_amount,
            'available' => Money::sub($a->balance, $a->held_amount), 'updated_at' => $a->updated_at?->toIso8601String(),
        ]);
    }

    public function ledger(Request $request, Student $student): JsonResponse
    {
        $account = StudentDepositAccount::query()->where('student_id', $student->id)->first();
        $entries = $account ? StudentDepositEntry::query()->where('account_id', $account->id)->with('creator')->latest()->limit(500)->get() : collect();

        return ApiResponse::success([
            'student' => Presenter::student($student->load('studyProgram')),
            'balance' => $account ? (string) $account->balance : '0.00',
            'held_amount' => $account ? (string) $account->held_amount : '0.00',
            'entries' => $entries->map(fn (StudentDepositEntry $e) => [
                'id' => $e->id, 'entry_type' => $e->entry_type->value, 'entry_type_label' => $e->entry_type->label(), 'amount' => (string) $e->amount,
                'balance_after' => (string) $e->balance_after, 'held_after' => (string) $e->held_after, 'reference_type' => $e->reference_type, 'reference_id' => $e->reference_id,
                'note' => $e->note, 'created_by' => $e->creator?->name, 'created_at' => $e->created_at?->toIso8601String(),
            ]),
        ]);
    }

    public function apply(Request $request, PaymentPostingService $posting, AuditLogService $audit): JsonResponse
    {
        $data = $request->validate(['invoice_id' => ['required', 'string'], 'installment_id' => ['nullable', 'string'], 'amount' => ['nullable', 'numeric', 'gt:0']]);
        $invoice = Invoice::query()->findOrFail($data['invoice_id']);
        $installment = isset($data['installment_id']) ? InvoiceInstallment::query()->where('invoice_id', $invoice->id)->findOrFail($data['installment_id']) : null;
        $tx = $posting->applyDeposit($invoice->student, $invoice, isset($data['amount']) ? (string) $data['amount'] : null, $installment, $this->user($request));
        $audit->recordAction($tx, AuditAction::Created, ['action' => 'apply_deposit', 'invoice' => $invoice->invoice_number, 'amount' => (string) $tx->amount]);

        return ApiResponse::success(Presenter::transaction($tx->load('allocations')), 'Saldo deposit dipakai untuk tagihan.');
    }

    public function refunds(Request $request): JsonResponse
    {
        $query = Refund::query()->with('student')->latest()
            ->when($request->filled('status'), fn ($q) => $q->whereIn('status', explode(',', $request->string('status'))))
            ->when($request->filled('student_id'), fn ($q) => $q->where('student_id', $request->string('student_id')));

        return $this->paginate($query, $request, fn (Refund $r) => Presenter::refund($r));
    }

    /** Membuka nomor rekening asli: hanya izin persetujuan/pelaksanaan & tercatat (BR-KEU-72). */
    public function showRefund(Request $request, Refund $refund, AuditLogService $audit): JsonResponse
    {
        $reveal = $request->boolean('reveal') && ($this->user($request)->hasPermissionTo('refunds.approve') || $this->user($request)->hasPermissionTo('refunds.finalize'));

        if ($reveal) {
            $audit->recordAction($refund, AuditAction::ViewedSensitive, ['field' => 'beneficiary_account']);
        }

        return ApiResponse::success(Presenter::refund($refund->load('student'), $reveal));
    }

    public function storeRefund(Request $request, RefundService $refunds): JsonResponse
    {
        $data = $request->validate([
            'student_id' => ['required', 'string'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'reason_code' => ['required', Rule::in(array_keys(RefundService::REASONS))],
            'reason_note' => ['required', 'string', 'min:10', 'max:2000'],
            'beneficiary_bank' => ['required', 'string', 'max:60'],
            'beneficiary_account' => ['required', 'regex:/^[0-9]{6,20}$/'],
            'beneficiary_name' => ['required', 'string', 'max:150'],
            'beneficiary_relation' => ['required', Rule::in(['self', 'parent', 'other'])],
            'power_of_attorney' => ['nullable', 'boolean'],
        ]);
        $refund = $refunds->request(Student::query()->findOrFail($data['student_id']), (string) $data['amount'], $data['reason_code'], $data['reason_note'], [
            'bank' => $data['beneficiary_bank'], 'account' => $data['beneficiary_account'], 'name' => $data['beneficiary_name'],
            'relation' => $data['beneficiary_relation'], 'power_of_attorney' => (bool) ($data['power_of_attorney'] ?? false),
        ], $this->user($request));

        return ApiResponse::success(Presenter::refund($refund), 'Pengembalian dana diajukan; saldo ditahan.', status: 201);
    }

    public function approveRefund(Request $request, Refund $refund, RefundService $refunds): JsonResponse
    {
        $data = $request->validate(['note' => ['nullable', 'string', 'max:1000']]);

        return ApiResponse::success(Presenter::refund($refunds->approve($refund, $this->user($request), $data['note'] ?? null)), 'Pengembalian dana disetujui.');
    }

    public function rejectRefund(Request $request, Refund $refund, RefundService $refunds): JsonResponse
    {
        $data = $request->validate(['note' => ['required', 'string', 'min:10', 'max:1000']]);

        return ApiResponse::success(Presenter::refund($refunds->reject($refund, $this->user($request), $data['note'])), 'Pengembalian dana ditolak; saldo dilepas.');
    }

    public function completeRefund(Request $request, Refund $refund, RefundService $refunds, FileAttacher $files): JsonResponse
    {
        $data = $request->validate(['execution_reference' => ['required', 'string', 'max:100'], 'proof_file_id' => ['nullable', 'string']]);
        $file = $files->resolve($data['proof_file_id'] ?? null, 'proof_file_id', $this->user($request), null, ['pdf', 'jpg', 'jpeg', 'png'], 5);
        $refund = $refunds->complete($refund, $data['execution_reference'], $file?->id, $this->user($request));
        $files->attach($file, $refund);

        return ApiResponse::success(Presenter::refund($refund), 'Pengembalian dana selesai dicatat.');
    }

    public function failRefund(Request $request, Refund $refund, RefundService $refunds): JsonResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:255']]);

        return ApiResponse::success(Presenter::refund($refunds->fail($refund, $data['reason'], $this->user($request))), 'Pengembalian dana gagal; saldo dikembalikan.');
    }

    public function cancelRefund(Request $request, Refund $refund, RefundService $refunds): JsonResponse
    {
        return ApiResponse::success(Presenter::refund($refunds->cancel($refund, $this->user($request))), 'Pengajuan dibatalkan.');
    }
}
