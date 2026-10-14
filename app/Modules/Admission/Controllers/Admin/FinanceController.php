<?php

namespace Modules\Admission\Controllers\Admin;

use App\Support\Http\ApiResponse;
use App\Support\Http\Exceptions\ConflictException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Admission\Controllers\Applicant\PaymentController;
use Modules\Admission\Enums\ManualProofStatus;
use Modules\Admission\Enums\PmbInvoiceStatus;
use Modules\Admission\Enums\ReconciliationResolution;
use Modules\Admission\Enums\RefundStatus;
use Modules\Admission\Enums\TransactionStatus;
use Modules\Admission\Models\ManualPaymentProof;
use Modules\Admission\Models\PaymentTransaction;
use Modules\Admission\Models\PmbInvoice;
use Modules\Admission\Models\ReconciliationBatch;
use Modules\Admission\Models\ReconciliationItem;
use Modules\Admission\Models\Refund;
use Modules\Admission\Models\WavePath;
use Modules\Admission\Services\Payment\PaymentService;
use Modules\Admission\Services\Payment\ReconciliationService;
use Modules\Admission\Services\Payment\RefundService;
use Modules\Admission\Support\Actor;
use Modules\Admission\Support\DocumentReasonCodes;
use Modules\Admission\Support\Money;
use Modules\Admission\Support\PmbFileStorage;
use Modules\Admission\Support\SensitiveAccessLogger;

/** Keuangan PMB: tagihan, transaksi, bukti manual, kasir, rekonsiliasi, refund (11 §A.7, ADM-30..35). */
class FinanceController extends AdminController
{
    public function __construct(private readonly PaymentService $payments) {}

    public function stats(Request $request): JsonResponse
    {
        $periodId = $request->string('period_id')->toString() ?: null;
        $invoices = fn () => PmbInvoice::query()->when($periodId, fn ($q) => $q->whereHas('application', fn ($a) => $a->where('period_id', $periodId)));
        $successToday = PaymentTransaction::query()->where('status', TransactionStatus::Success->value)->where('paid_at', '>=', now()->startOfDay())
            ->when($periodId, fn ($q) => $q->whereHas('invoice.application', fn ($a) => $a->where('period_id', $periodId)))->get(['amount']);

        return ApiResponse::success([
            'paid_registration' => $invoices()->where('type', 'registration_fee')->where('status', PmbInvoiceStatus::Paid->value)->count(),
            'unpaid_registration' => $invoices()->where('type', 'registration_fee')->whereIn('status', [PmbInvoiceStatus::Unpaid->value, PmbInvoiceStatus::Pending->value])->count(),
            'revenue_registration' => Money::add('0', ...$invoices()->where('type', 'registration_fee')->pluck('amount_paid')->map(fn ($v) => (string) $v)->all()),
            'revenue_initial_tuition' => Money::add('0', ...$invoices()->where('type', 'initial_tuition')->pluck('amount_paid')->map(fn ($v) => (string) $v)->all()),
            'today_transactions' => $successToday->count(),
            'today_amount' => Money::add('0', ...$successToday->map(fn ($t) => (string) $t->amount)->all()),
            'pending_manual_proofs' => ManualPaymentProof::query()->where('status', ManualProofStatus::Submitted->value)->count(),
            'needs_resolution' => $invoices()->where('needs_resolution', true)->count(),
            'refunds_pending' => Refund::query()->whereIn('status', [RefundStatus::PendingApproval->value, RefundStatus::Approved->value, RefundStatus::Processing->value])->count(),
            'channels' => $this->payments->channels(),
        ]);
    }

    public function invoices(Request $request): JsonResponse
    {
        $query = PmbInvoice::query()->with(['application:id,registration_number,applicant_account_id,period_id', 'application.account:id,name,email'])
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->when($request->filled('status'), fn ($q) => $q->whereIn('status', explode(',', $request->string('status'))))
            ->when($request->boolean('needs_resolution'), fn ($q) => $q->where('needs_resolution', true))
            ->when($request->filled('period_id'), fn ($q) => $q->whereHas('application', fn ($a) => $a->where('period_id', $request->string('period_id'))))
            ->when($request->filled('search'), function ($q) use ($request): void {
                $term = '%'.$request->string('search').'%';
                $q->where(fn ($w) => $w->where('invoice_number', 'like', $term)->orWhereHas('application', fn ($a) => $a->where('registration_number', 'like', $term)
                    ->orWhereHas('account', fn ($ac) => $ac->where('name', 'like', $term)->orWhere('email', 'like', $term))));
            })
            ->orderByDesc('created_at');

        return $this->paginate($query, $request, fn (PmbInvoice $i) => [
            ...PaymentController::presentInvoice($i),
            'application' => ['id' => $i->application_id, 'registration_number' => $i->application?->registration_number, 'name' => $i->application?->account?->name, 'email' => $i->application?->account?->email],
            'finance_invoice_id' => $i->finance_invoice_id,
            'resolution_note' => $i->resolution_note,
        ]);
    }

    public function invoice(PmbInvoice $pmbInvoice): JsonResponse
    {
        $pmbInvoice->load(['items', 'transactions.manualProof', 'application.account:id,name,email,phone']);
        $refunds = Refund::query()->whereIn('transaction_id', $pmbInvoice->transactions->pluck('id'))->get();

        return ApiResponse::success([
            ...PaymentController::presentInvoice($pmbInvoice),
            'application' => ['id' => $pmbInvoice->application_id, 'registration_number' => $pmbInvoice->application?->registration_number, 'name' => $pmbInvoice->application?->account?->name, 'email' => $pmbInvoice->application?->account?->email],
            'transactions' => $pmbInvoice->transactions->map(fn ($t) => [...PaymentController::presentTransaction($t), 'provider_reference' => $t->provider_reference, 'idempotency_key' => $t->idempotency_key, 'provider_status_raw' => $t->provider_status_raw, 'failure_reason' => $t->failure_reason, 'recorded_by' => $t->recorded_by]),
            'refunds' => $refunds->map(fn (Refund $r) => $this->presentRefund($r)),
            'resolution_note' => $pmbInvoice->resolution_note,
            'finance_invoice_id' => $pmbInvoice->finance_invoice_id,
        ]);
    }

    public function transactions(Request $request): JsonResponse
    {
        $query = PaymentTransaction::query()->with(['invoice:id,invoice_number,application_id,type', 'invoice.application:id,registration_number,applicant_account_id', 'invoice.application.account:id,name'])
            ->when($request->filled('status'), fn ($q) => $q->whereIn('status', explode(',', $request->string('status'))))
            ->when($request->filled('channel'), fn ($q) => $q->where('channel', $request->string('channel')))
            ->when($request->filled('provider'), fn ($q) => $q->where('provider', $request->string('provider')))
            ->when($request->filled('date_from'), fn ($q) => $q->where('created_at', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->where('created_at', '<', $request->date('date_to')?->addDay()))
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($w) => $w->where('provider_reference', 'like', '%'.$request->string('search').'%')
                ->orWhere('receipt_number', 'like', '%'.$request->string('search').'%')
                ->orWhereHas('invoice', fn ($i) => $i->where('invoice_number', 'like', '%'.$request->string('search').'%'))))
            ->orderByDesc('created_at');

        return $this->paginate($query, $request, fn (PaymentTransaction $t) => [
            ...PaymentController::presentTransaction($t),
            'provider_reference' => $t->provider_reference,
            'invoice' => ['id' => $t->invoice_id, 'number' => $t->invoice?->invoice_number, 'type' => $t->invoice?->type?->value],
            'applicant' => $t->invoice?->application?->account?->name,
            'registration_number' => $t->invoice?->application?->registration_number,
            'recheck_attempts' => $t->recheck_attempts,
        ]);
    }

    public function callbacks(PaymentTransaction $paymentTransaction): JsonResponse
    {
        // Header & payload sudah tanpa rahasia (tanda tangan disimpan sebagai boolean).
        return ApiResponse::success($paymentTransaction->callbacks()->orderByDesc('received_at')->get()
            ->map->only(['id', 'provider', 'provider_event_id', 'signature_valid', 'payload', 'received_at', 'processed_at', 'processing_result', 'error_message', 'attempts']));
    }

    public function recheck(Request $request, PaymentTransaction $paymentTransaction): JsonResponse
    {
        $transaction = $this->payments->recheck($paymentTransaction, Actor::user($this->user($request)));

        return ApiResponse::success(PaymentController::presentTransaction($transaction), 'Status transaksi diperbarui dari penyedia.');
    }

    public function resolve(Request $request, PmbInvoice $pmbInvoice): JsonResponse
    {
        $data = $request->validate(['resolution' => ['required', Rule::in(['accept_and_reopen', 'refund'])], 'note' => ['required', 'string', 'min:5', 'max:500']]);
        $invoice = $this->payments->resolveLatePayment($pmbInvoice, $data['resolution'], $data['note'], $this->user($request));

        return ApiResponse::success(PaymentController::presentInvoice($invoice), 'Resolusi tagihan disimpan.');
    }

    // ------------------------------------------------------------------ Bukti manual & kasir

    public function manualProofs(Request $request): JsonResponse
    {
        $query = ManualPaymentProof::query()->with(['transaction.invoice.application.account:id,name', 'reviewer:id,name'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')), fn ($q) => $q->where('status', ManualProofStatus::Submitted->value))
            ->orderBy('created_at');

        return $this->paginate($query, $request, fn (ManualPaymentProof $p) => [
            'id' => $p->getKey(), 'status' => $p->status->value,
            'sender_bank' => $p->sender_bank, 'sender_name' => $p->sender_name, 'transfer_date' => $p->transfer_date?->toDateString(),
            'transferred_amount' => (string) $p->transferred_amount, 'expected_amount' => (string) $p->transaction?->amount,
            'unique_code' => $p->transaction?->unique_code, 'duplicate_warning' => $p->duplicate_warning,
            'invoice_number' => $p->transaction?->invoice?->invoice_number, 'invoice_type' => $p->transaction?->invoice?->type?->value,
            'registration_number' => $p->transaction?->invoice?->application?->registration_number, 'applicant' => $p->transaction?->invoice?->application?->account?->name,
            'reason_code' => $p->reason_code, 'note' => $p->note, 'reviewer' => $p->reviewer?->name, 'reviewed_at' => $p->reviewed_at?->toIso8601String(),
            'created_at' => $p->created_at?->toIso8601String(),
        ]);
    }

    public function proofFile(Request $request, ManualPaymentProof $manualPaymentProof, PmbFileStorage $storage): JsonResponse
    {
        abort_if($manualPaymentProof->fileUpload === null, 404);

        return ApiResponse::success(['url' => $storage->temporaryUrl($manualPaymentProof->fileUpload), 'mime_type' => $manualPaymentProof->fileUpload->mime_type])->header('Cache-Control', 'no-store');
    }

    public function decideProof(Request $request, ManualPaymentProof $manualPaymentProof): JsonResponse
    {
        $data = $request->validate([
            'approve' => ['required', 'boolean'],
            'reason_code' => ['required_if:approve,false', 'nullable', Rule::in(DocumentReasonCodes::MANUAL_PROOF_REJECTION)],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);
        $proof = $this->payments->decideManualProof($manualPaymentProof, (bool) $data['approve'], $data['reason_code'] ?? null, $data['note'] ?? null, $this->user($request));

        return ApiResponse::success(['id' => $proof->getKey(), 'status' => $proof->status->value], $data['approve'] ? 'Pembayaran dikonfirmasi.' : 'Bukti pembayaran ditolak.');
    }

    public function cashier(Request $request, PmbInvoice $pmbInvoice): JsonResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'receipt_number' => ['required', 'string', 'max:50'],
            'paid_at' => ['required', 'date', 'before_or_equal:now'],
        ]);
        $transaction = $this->payments->recordCashierPayment($pmbInvoice, (string) $data['amount'], $data['receipt_number'], $data['paid_at'], $this->user($request));

        return ApiResponse::success(PaymentController::presentTransaction($transaction), 'Pembayaran loket dicatat.', status: 201);
    }

    // ------------------------------------------------------------------ Rekonsiliasi

    public function batches(Request $request): JsonResponse
    {
        return $this->paginate(ReconciliationBatch::query()->orderByDesc('statement_date'), $request);
    }

    public function storeBatch(Request $request, ReconciliationService $reconciliation): JsonResponse
    {
        $data = $request->validate(['provider' => ['required', 'string', 'max:30'], 'statement_date' => ['required', 'date', 'before_or_equal:today']]);

        return ApiResponse::success($reconciliation->createBatch($data['provider'], $data['statement_date'], $this->user($request)), 'Batch rekonsiliasi dibuat.', status: 201);
    }

    public function batch(Request $request, ReconciliationBatch $reconciliationBatch): JsonResponse
    {
        $items = $reconciliationBatch->items()->with('transaction:id,invoice_id,amount,status,provider_reference')
            ->when($request->filled('result'), fn ($q) => $q->where('result', $request->string('result')))
            ->orderBy('result')->get();

        return ApiResponse::success([...$reconciliationBatch->toArray(), 'items' => $items, 'resolutions' => ReconciliationResolution::values()]);
    }

    public function importStatement(Request $request, ReconciliationBatch $reconciliationBatch, ReconciliationService $reconciliation): JsonResponse
    {
        $request->validate(['file' => ['required', 'file', 'max:10240']]);

        return ApiResponse::success($reconciliation->importBankStatement($reconciliationBatch, $request->file('file'), $this->user($request)), 'Mutasi diimpor dan dicocokkan.');
    }

    public function resolveItem(Request $request, ReconciliationItem $reconciliationItem, ReconciliationService $reconciliation): JsonResponse
    {
        $data = $request->validate(['resolution' => ['required', Rule::in(ReconciliationResolution::values())], 'note' => ['required', 'string', 'min:5', 'max:1000']]);

        return ApiResponse::success($reconciliation->resolveItem($reconciliationItem, $data['resolution'], $data['note'], $this->user($request)), 'Item diselesaikan.');
    }

    public function closeBatch(Request $request, ReconciliationBatch $reconciliationBatch, ReconciliationService $reconciliation): JsonResponse
    {
        return ApiResponse::success($reconciliation->closeBatch($reconciliationBatch, $this->user($request)), 'Batch ditutup.');
    }

    // ------------------------------------------------------------------ Refund

    public function refunds(Request $request): JsonResponse
    {
        $query = Refund::query()->with(['transaction.invoice.application.account:id,name', 'requester:id,name'])
            ->when($request->filled('status'), fn ($q) => $q->whereIn('status', explode(',', $request->string('status'))))
            ->orderByDesc('created_at');

        return $this->paginate($query, $request, fn (Refund $r) => $this->presentRefund($r));
    }

    public function requestRefund(Request $request, RefundService $refunds): JsonResponse
    {
        $data = $this->validateRefund($request);
        $transaction = PaymentTransaction::query()->findOrFail($data['transaction_id']);
        $refund = $refunds->request($transaction, $data, $this->user($request));

        return ApiResponse::success($this->presentRefund($refund), 'Pengajuan refund dikirim untuk persetujuan.', status: 201);
    }

    /** Refund draft (mis. hasil resolusi otomatis) dilengkapi data rekening lalu diajukan. */
    public function submitDraftRefund(Request $request, Refund $refund, RefundService $refunds): JsonResponse
    {
        if ($refund->status !== RefundStatus::Draft) {
            throw new ConflictException('Hanya refund draft yang dapat diajukan.', 'INVALID_TRANSITION');
        }

        $data = $this->validateRefund($request, false);
        $refund = $refunds->request($refund->transaction, $data, $this->user($request), $refund);

        return ApiResponse::success($this->presentRefund($refund), 'Refund diajukan untuk persetujuan.');
    }

    public function completeRefund(Request $request, Refund $refund, RefundService $refunds, PmbFileStorage $storage): JsonResponse
    {
        $data = $request->validate(['proof' => ['nullable', 'file', 'max:5120'], 'completed_at' => ['nullable', 'date', 'before_or_equal:now']]);
        $fileId = $request->hasFile('proof')
            ? $storage->store($request->file('proof'), ['image/jpeg', 'image/png', 'application/pdf'], 5120, ['refund', (string) $refund->getKey()], uploadedByUserId: (string) $this->user($request)->getKey())->getKey()
            : null;
        $refund = $refunds->complete($refund, $fileId, $data['completed_at'] ?? null, $this->user($request));

        return ApiResponse::success($this->presentRefund($refund), 'Refund dicatat selesai.');
    }

    public function bulkRefund(Request $request, WavePath $wavePath, RefundService $refunds): JsonResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:1000']]);
        $change = $refunds->requestBulkForWavePath($wavePath, $data['reason'], $this->user($request));

        return ApiResponse::success(['change_request_id' => $change->getKey(), 'status' => $change->status->value], 'Pengajuan refund massal dikirim untuk persetujuan.', status: 201);
    }

    /** @return array<string, mixed> */
    private function validateRefund(Request $request, bool $withTransaction = true): array
    {
        return $request->validate([
            'transaction_id' => [$withTransaction ? 'required' : 'prohibited', 'string', 'size:26'],
            'amount' => ['required', 'numeric', 'min:1'],
            'reason_code' => ['required', Rule::in(DocumentReasonCodes::REFUND)],
            'reason_note' => ['required', 'string', 'min:5', 'max:1000'],
            'beneficiary_bank' => ['required', 'string', 'max:100'],
            'beneficiary_account' => ['required', 'string', 'max:40', 'regex:/^[0-9 -]+$/'],
            'beneficiary_name' => ['required', 'string', 'max:150'],
        ]);
    }

    /** @return array<string, mixed> */
    private function presentRefund(Refund $r): array
    {
        return [
            'id' => $r->getKey(), 'status' => $r->status->value, 'amount' => (string) $r->amount,
            'reason_code' => $r->reason_code, 'reason_note' => $r->reason_note,
            'beneficiary_bank' => $r->beneficiary_bank, 'beneficiary_account_masked' => SensitiveAccessLogger::mask($r->beneficiary_account, 4), 'beneficiary_name' => $r->beneficiary_name,
            'transaction_id' => $r->transaction_id, 'invoice_number' => $r->transaction?->invoice?->invoice_number,
            'applicant' => $r->transaction?->invoice?->application?->account?->name,
            'requested_by' => $r->requester?->name, 'approval_request_id' => $r->approval_request_id,
            'completed_at' => $r->completed_at?->toIso8601String(), 'created_at' => $r->created_at?->toIso8601String(),
        ];
    }
}
