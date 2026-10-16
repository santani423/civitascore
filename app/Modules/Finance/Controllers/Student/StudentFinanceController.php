<?php

namespace Modules\Finance\Controllers\Student;

use App\Support\Http\ApiResponse;
use App\Support\Http\Exceptions\ConflictException;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Modules\Academic\Models\AcademicBlock;
use Modules\Academic\Models\Student;
use Modules\Academic\Services\AcademicBlockService;
use Modules\FileManagement\Support\FileAttacher;
use Modules\Finance\Controllers\Admin\PolicyController;
use Modules\Finance\Controllers\KeuController;
use Modules\Finance\Controllers\ReceiptController;
use Modules\Finance\Enums\ChannelStatus;
use Modules\Finance\Enums\InvoiceStatus;
use Modules\Finance\Enums\TransactionStatus;
use Modules\Finance\Models\FinancialAidAward;
use Modules\Finance\Models\InstallmentPlan;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\InvoiceInstallment;
use Modules\Finance\Models\PaymentChannel;
use Modules\Finance\Models\PaymentTransaction;
use Modules\Finance\Models\Refund;
use Modules\Finance\Models\StudentDepositAccount;
use Modules\Finance\Models\StudentDepositEntry;
use Modules\Finance\Models\StudentUktAssignment;
use Modules\Finance\Models\UktChangeRequest;
use Modules\Finance\Models\UktGroup;
use Modules\Finance\Services\ChannelService;
use Modules\Finance\Services\ClearanceService;
use Modules\Finance\Services\DepositService;
use Modules\Finance\Services\InstallmentService;
use Modules\Finance\Services\ManualPaymentService;
use Modules\Finance\Services\OnlinePaymentService;
use Modules\Finance\Services\PaymentPostingService;
use Modules\Finance\Services\RefundService;
use Modules\Finance\Services\StatementService;
use Modules\Finance\Services\UktService;
use Modules\Finance\Services\VoucherService;
use Modules\Finance\Support\KeuSettings;
use Modules\Finance\Support\Money;
use Modules\Finance\Support\Presenter;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Layanan mandiri keuangan mahasiswa (F-16). Mahasiswa selalu diresolusi
 * dari akun yang login (`students.user_id`); objek milik mahasiswa lain → 404.
 */
class StudentFinanceController extends KeuController
{
    public function summary(Request $request, ClearanceService $clearance, DepositService $deposits, AcademicBlockService $blocks): JsonResponse
    {
        $student = $this->student($request);
        $open = Invoice::query()->where('student_id', $student->id)->whereIn('status', InvoiceStatus::open())->get();
        $next = InvoiceInstallment::query()->whereIn('invoice_id', $open->pluck('id'))->whereNull('superseded_at')->whereColumn('paid_amount', '<', 'amount')->orderBy('due_date')->first();
        $nextInvoice = $open->sortBy('due_date')->first();

        return ApiResponse::success([
            'student' => Presenter::student($student->load('studyProgram')),
            'outstanding' => Money::fromCents($open->sum(fn ($i) => Money::toCents($i->outstanding()))),
            'arrears' => Money::fromCents($open->where('is_overdue', true)->sum(fn ($i) => Money::toCents($i->outstanding()))),
            'open_invoices' => $open->count(),
            'next_due' => $next ? ['date' => $next->due_date->toDateString(), 'amount' => Money::sub($next->amount, $next->paid_amount), 'invoice_id' => $next->invoice_id, 'installment_seq' => $next->seq]
                : ($nextInvoice ? ['date' => $nextInvoice->due_date->toDateString(), 'amount' => $nextInvoice->outstanding(), 'invoice_id' => $nextInvoice->id, 'installment_seq' => null] : null),
            'deposit_available' => Money::fromCents($deposits->available($student)),
            'pending_verification' => PaymentTransaction::query()->where('student_id', $student->id)->where('status', TransactionStatus::AwaitingVerification)->count(),
            'services' => collect(['krs', 'exam'])->map(fn ($s) => ['service' => $s, 'label' => AcademicBlockService::SERVICES[$s], 'blocked' => $blocks->blockers($student, $s) !== []])->values(),
            'clearance' => $clearance->clearance($student),
            'blocks' => AcademicBlock::query()->where('student_id', $student->id)->whereIn('status', ['active', 'exempted'])->with('exemptions')->get()->map(fn ($b) => Presenter::block($b)),
        ]);
    }

    public function invoices(Request $request): JsonResponse
    {
        $student = $this->student($request);
        $query = Invoice::query()->where('student_id', $student->id)->where('status', '!=', InvoiceStatus::Draft)->orderByDesc('due_date')
            ->when($request->filled('status'), fn ($q) => $q->whereIn('status', explode(',', $request->string('status'))));

        return $this->paginate($query, $request, fn (Invoice $i) => Presenter::invoice($i, forStudent: true));
    }

    public function invoice(Request $request, string $invoice): JsonResponse
    {
        $model = $this->ownInvoice($request, $invoice)->load(['items', 'installments', 'payments', 'penaltyInvoices']);

        return ApiResponse::success([
            ...Presenter::invoice($model, detail: true, forStudent: true),
            'pending_transactions' => PaymentTransaction::query()->where('student_id', $model->student_id)->whereIn('status', [TransactionStatus::Pending, TransactionStatus::AwaitingVerification])
                ->where(fn ($q) => $q->where('target_id', $model->id)->orWhereIn('target_id', $model->installments->pluck('id')))->with(['channel', 'proof'])->latest()->get()
                ->map(fn ($t) => Presenter::transaction($t, forStudent: true)),
        ]);
    }

    public function channels(ChannelService $channels, KeuSettings $settings): JsonResponse
    {
        return ApiResponse::success([
            'online' => PaymentChannel::query()->where('status', ChannelStatus::Active)->where('category', '!=', 'offline')->orderBy('sort_order')->get()
                ->filter(fn (PaymentChannel $c) => $channels->isUsable($c))->values()
                ->map(fn (PaymentChannel $c) => ['id' => $c->id, 'code' => $c->code, 'name' => $c->name, 'method' => $c->method, 'fee_amount' => (string) $c->fee_amount, 'fee_bearer' => $c->fee_bearer]),
            'manual_transfer' => ['enabled' => true, 'bank_accounts' => (array) $settings->get('keu.bank_accounts'), 'unique_code' => $settings->bool('keu.manual_transfer.unique_code')],
        ]);
    }

    public function payOnline(Request $request, OnlinePaymentService $online): JsonResponse
    {
        $data = $request->validate(['payment_channel_id' => ['required', 'string'], 'target_type' => ['required', Rule::in(['invoice', 'installment'])], 'target_id' => ['required', 'string']]);
        $key = (string) $request->header('Idempotency-Key');

        if ($key === '' || strlen($key) > 100) {
            throw new ConflictException('Header Idempotency-Key wajib diisi.', 'IDEMPOTENCY_REQUIRED');
        }

        $tx = $online->create($this->student($request), PaymentChannel::query()->findOrFail($data['payment_channel_id']), $data['target_type'], $data['target_id'], $key, $this->user($request));

        return ApiResponse::success(Presenter::transaction($tx->load('channel'), forStudent: true), 'Instruksi pembayaran dibuat.', status: 201);
    }

    public function prepareManual(Request $request, ManualPaymentService $manual): JsonResponse
    {
        $data = $request->validate(['target_type' => ['required', Rule::in(['invoice', 'installment', 'deposit'])], 'target_id' => ['nullable', 'required_unless:target_type,deposit', 'string'], 'amount' => ['nullable', 'required_if:target_type,deposit', 'numeric', 'gt:0']]);
        $tx = $manual->prepare($this->student($request), $data['target_type'], $data['target_id'] ?? null, isset($data['amount']) ? (string) $data['amount'] : null, $this->user($request));

        return ApiResponse::success(Presenter::transaction($tx, forStudent: true), 'Silakan transfer sesuai nominal lalu unggah bukti.', status: 201);
    }

    public function uploadProof(Request $request, string $transaction, ManualPaymentService $manual, FileAttacher $files): JsonResponse
    {
        $tx = $this->ownTransaction($request, $transaction);
        $data = $request->validate([
            'file_upload_id' => ['required', 'string'],
            'sender_bank' => ['required', 'string', 'max:60'],
            'sender_name' => ['required', 'string', 'max:150'],
            'transfer_date' => ['required', 'date', 'before_or_equal:today'],
            'transferred_amount' => ['required', 'numeric', 'gt:0'],
        ]);
        $file = $files->resolve($data['file_upload_id'], 'file_upload_id', $this->user($request), null, ['jpg', 'jpeg', 'png', 'pdf'], 2);
        $proof = $manual->submitProof($tx, $file, $data['sender_bank'], $data['sender_name'], $data['transfer_date'], (string) $data['transferred_amount'], $this->user($request));

        return ApiResponse::success(Presenter::transaction($proof->transaction->load('proof'), forStudent: true), 'Bukti transfer terkirim dan menunggu verifikasi Bagian Keuangan.');
    }

    public function cancelTransaction(Request $request, string $transaction, OnlinePaymentService $online): JsonResponse
    {
        return ApiResponse::success(Presenter::transaction($online->cancel($this->ownTransaction($request, $transaction)), forStudent: true), 'Transaksi dibatalkan.');
    }

    /** Status dari halaman redirect penyedia hanya memicu cek ulang ke server (BR-KEU-41). */
    public function refreshTransaction(Request $request, string $transaction, OnlinePaymentService $online): JsonResponse
    {
        $tx = $this->ownTransaction($request, $transaction);

        if ($tx->status === TransactionStatus::Pending) {
            $online->recheck($tx);
        }

        return ApiResponse::success(Presenter::transaction($tx->fresh()->load(['channel', 'proof']), forStudent: true));
    }

    public function transactions(Request $request): JsonResponse
    {
        $query = PaymentTransaction::query()->where('student_id', $this->student($request)->id)->with(['channel', 'proof'])->latest();

        return $this->paginate($query, $request, fn (PaymentTransaction $t) => Presenter::transaction($t, forStudent: true));
    }

    public function receipt(Request $request, string $transaction, ReceiptController $receipts): Response
    {
        return $receipts->render($this->ownTransaction($request, $transaction));
    }

    public function applyDeposit(Request $request, string $invoice, PaymentPostingService $posting): JsonResponse
    {
        $model = $this->ownInvoice($request, $invoice);
        $data = $request->validate(['amount' => ['nullable', 'numeric', 'gt:0'], 'installment_id' => ['nullable', 'string']]);
        $installment = isset($data['installment_id']) ? InvoiceInstallment::query()->where('invoice_id', $model->id)->findOrFail($data['installment_id']) : null;
        $tx = $posting->applyDeposit($this->student($request), $model, isset($data['amount']) ? (string) $data['amount'] : null, $installment, $this->user($request));

        return ApiResponse::success(Presenter::transaction($tx, forStudent: true), 'Saldo deposit dipakai sebesar '.Money::format($tx->amount).'.');
    }

    public function redeemVoucher(Request $request, string $invoice, VoucherService $vouchers): JsonResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'between:4,30']]);
        $redemption = $vouchers->redeem($this->ownInvoice($request, $invoice), $data['code'], $this->user($request));

        return ApiResponse::success(['discount_amount' => (string) $redemption->discount_amount], 'Voucher berhasil dipakai: potongan '.Money::format($redemption->discount_amount).'.');
    }

    public function installmentPlans(Request $request, string $invoice): JsonResponse
    {
        $model = $this->ownInvoice($request, $invoice);

        return ApiResponse::success(InstallmentPlan::query()->where('is_active', true)->get()
            ->filter(fn (InstallmentPlan $p) => in_array($model->bill_group->value, $p->bill_groups, true))->values()
            ->map(fn (InstallmentPlan $p) => ['id' => $p->id, 'code' => $p->code, 'name' => $p->name, 'terms' => $p->terms, 'auto_approve' => $p->auto_approve]));
    }

    public function requestInstallment(Request $request, string $invoice, InstallmentService $installments): JsonResponse
    {
        $model = $this->ownInvoice($request, $invoice);
        $data = PolicyController::validateInstallmentRequest($request);

        if (empty($data['installment_plan_id']) && ! empty($data['terms']) && empty($data['reason'])) {
            throw new ConflictException('Pengajuan cicilan khusus wajib disertai alasan.');
        }

        $plan = ! empty($data['installment_plan_id']) ? InstallmentPlan::query()->where('is_active', true)->findOrFail($data['installment_plan_id']) : null;
        $result = $installments->request($model, $plan, $plan ? null : ($data['terms'] ?? null), $data['reason'] ?? null, $this->user($request), $data['kind'] ?? 'new');

        return ApiResponse::success(['id' => $result->id, 'status' => $result->status->value], $result->status->value === 'approved' ? 'Cicilan disetujui; jadwal termin tersedia.' : 'Pengajuan cicilan dikirim dan menunggu persetujuan.', status: 201);
    }

    public function deposit(Request $request): JsonResponse
    {
        $student = $this->student($request);
        $account = StudentDepositAccount::query()->where('student_id', $student->id)->first();

        return ApiResponse::success([
            'balance' => $account ? (string) $account->balance : '0.00',
            'held_amount' => $account ? (string) $account->held_amount : '0.00',
            'available' => $account ? Money::sub($account->balance, $account->held_amount) : '0.00',
            'entries' => $account ? StudentDepositEntry::query()->where('account_id', $account->id)->latest()->limit(200)->get()->map(fn (StudentDepositEntry $e) => [
                'id' => $e->id, 'type' => $e->entry_type->value, 'type_label' => $e->entry_type->label(), 'amount' => (string) $e->amount, 'balance_after' => (string) $e->balance_after,
                'note' => $e->note, 'created_at' => $e->created_at?->toIso8601String(),
            ]) : [],
            'refunds' => Refund::query()->where('student_id', $student->id)->latest()->get()->map(fn (Refund $r) => Presenter::refund($r)),
        ]);
    }

    public function requestRefund(Request $request, RefundService $refunds): JsonResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'reason_code' => ['required', Rule::in(array_keys(RefundService::REASONS))],
            'reason_note' => ['required', 'string', 'min:10', 'max:1000'],
            'beneficiary_bank' => ['required', 'string', 'max:60'],
            'beneficiary_account' => ['required', 'regex:/^[0-9]{6,20}$/'],
            'beneficiary_name' => ['required', 'string', 'max:150'],
            'beneficiary_relation' => ['required', Rule::in(['self', 'parent'])],
        ]);
        $refund = $refunds->request($this->student($request), (string) $data['amount'], $data['reason_code'], $data['reason_note'], [
            'bank' => $data['beneficiary_bank'], 'account' => $data['beneficiary_account'], 'name' => $data['beneficiary_name'], 'relation' => $data['beneficiary_relation'],
        ], $this->user($request));

        return ApiResponse::success(Presenter::refund($refund), 'Pengajuan pengembalian dana dikirim; saldo ditahan sampai diputuskan.', status: 201);
    }

    public function statement(Request $request, StatementService $statements): JsonResponse
    {
        return ApiResponse::success($statements->forStudent($this->student($request)));
    }

    public function ukt(Request $request): JsonResponse
    {
        $student = $this->student($request);

        $current = StudentUktAssignment::query()->where('student_id', $student->id)->where('status', 'active')->with(['group', 'effectiveFromTerm'])->first();

        return ApiResponse::success([
            'current' => $current ? [
                'group' => $current->group?->only(['id', 'code', 'name']), 'basis' => $current->basis, 'decree_number' => $current->decree_number,
                'effective_from' => $current->effectiveFromTerm?->label(),
            ] : null,
            'groups' => UktGroup::query()->where('is_active', true)->orderBy('level')->get(['id', 'code', 'name']),
            'requests' => UktChangeRequest::query()->where('student_id', $student->id)->with(['requestedGroup', 'decidedGroup'])->latest()->get()->map(fn (UktChangeRequest $r) => [
                'id' => $r->id, 'requested_group' => $r->requestedGroup?->name, 'decided_group' => $r->decidedGroup?->name, 'reason' => $r->reason, 'status' => $r->status->value,
                'status_label' => $r->status->label(), 'decision_note' => $r->decision_note, 'created_at' => $r->created_at?->toIso8601String(),
            ]),
        ]);
    }

    public function requestUktChange(Request $request, UktService $ukt, FileAttacher $files): JsonResponse
    {
        $data = $request->validate(['requested_group_id' => ['nullable', 'string'], 'reason' => ['required', 'string', 'min:20', 'max:2000'], 'evidence_file_id' => ['required', 'string']]);
        $file = $files->resolve($data['evidence_file_id'], 'evidence_file_id', $this->user($request), null, ['pdf', 'jpg', 'jpeg', 'png'], 5);
        $group = ! empty($data['requested_group_id']) ? UktGroup::query()->where('is_active', true)->findOrFail($data['requested_group_id']) : null;
        $change = $ukt->requestChange($this->student($request), $group, $data['reason'], $file->id, $this->user($request));
        $files->attach($file, $change);

        return ApiResponse::success(['id' => $change->id], 'Pengajuan peninjauan UKT dikirim.', status: 201);
    }

    public function awards(Request $request): JsonResponse
    {
        return ApiResponse::success(FinancialAidAward::query()->where('student_id', $this->student($request)->id)->whereIn('status', ['active', 'revoked'])->with(['policy', 'startTerm', 'endTerm'])->latest()->get()->map(fn ($a) => Presenter::award($a)));
    }

    public function requestDispensation(Request $request, string $block, ClearanceService $clearance): JsonResponse
    {
        $student = $this->student($request);
        $model = AcademicBlock::query()->where('student_id', $student->id)->where('source', ClearanceService::SOURCE)->find($block) ?? throw new NotFoundHttpException;
        $data = $request->validate([
            'service' => ['required', Rule::in(array_keys(AcademicBlockService::SERVICES))],
            'valid_until' => ['required', 'date', 'after:now'],
            'reason' => ['required', 'string', 'min:20', 'max:1000'],
            'promise_amount' => ['nullable', 'numeric', 'gt:0'],
            'promise_date' => ['nullable', 'date', 'after_or_equal:today'],
        ]);
        $exemption = $clearance->requestDispensation($model, $data['service'], CarbonImmutable::parse($data['valid_until']), $data['reason'], $this->user($request), true,
            isset($data['promise_amount']) ? (string) $data['promise_amount'] : null, $data['promise_date'] ?? null);

        return ApiResponse::success(Presenter::exemption($exemption), 'Pengajuan dispensasi dikirim ke Bagian Keuangan.', status: 201);
    }

    private function student(Request $request): Student
    {
        return Student::query()->where('user_id', $this->user($request)->id)->first() ?? abort(403, 'Akun ini tidak terhubung dengan data mahasiswa.');
    }

    private function ownInvoice(Request $request, string $id): Invoice
    {
        return Invoice::query()->where('student_id', $this->student($request)->id)->where('status', '!=', InvoiceStatus::Draft)->find($id) ?? throw new NotFoundHttpException;
    }

    private function ownTransaction(Request $request, string $id): PaymentTransaction
    {
        return PaymentTransaction::query()->where('student_id', $this->student($request)->id)->find($id) ?? throw new NotFoundHttpException;
    }
}
