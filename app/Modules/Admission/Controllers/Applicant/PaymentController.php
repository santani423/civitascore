<?php

namespace Modules\Admission\Controllers\Applicant;

use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Admission\Models\PaymentTransaction;
use Modules\Admission\Models\PmbInvoice;
use Modules\Admission\Services\Payment\InvoiceService;
use Modules\Admission\Services\Payment\PaymentService;
use Modules\Admission\Support\PmbFileStorage;

/**
 * Pembayaran pendaftar (docs/pmb/11 §A.7). Status dari klien TIDAK pernah
 * mengubah status pembayaran — hanya memicu pengecekan ulang (BR-15).
 */
class PaymentController extends ApplicantController
{
    public function __construct(private readonly PaymentService $payments, private readonly InvoiceService $invoices) {}

    public function channels(): JsonResponse
    {
        return ApiResponse::success($this->payments->channels());
    }

    public function invoices(Request $request, string $id): JsonResponse
    {
        $application = $this->application($request, $id);
        $this->invoices->ensureRegistrationInvoice($application);

        return ApiResponse::success($application->invoices()->with(['items', 'transactions.manualProof'])->latest()->get()->map(fn (PmbInvoice $i) => $this->presentInvoice($i))->values());
    }

    public function applyVoucher(Request $request, string $invoiceId): JsonResponse
    {
        $invoice = $this->invoice($request, $invoiceId);
        $code = (string) $request->validate(['code' => ['required', 'string', 'max:30']])['code'];

        return ApiResponse::success($this->presentInvoice($this->invoices->applyVoucher($invoice, $code)->load(['items', 'transactions'])), 'Voucher diterapkan.');
    }

    public function createTransaction(Request $request, string $invoiceId): JsonResponse
    {
        $invoice = $this->invoice($request, $invoiceId);
        $data = $request->validate(['channel' => ['required', 'string', 'max:30'], 'amount' => ['nullable', 'numeric', 'min:1']]);
        $transaction = $this->payments->createTransaction($invoice, $data['channel'], isset($data['amount']) ? (string) $data['amount'] : null, (string) $request->header('Idempotency-Key'), $this->actor($request));

        return ApiResponse::success([
            'transaction' => $this->presentTransaction($transaction),
            'invoice' => $this->presentInvoice($invoice->refresh()->load(['items', 'transactions'])),
        ], 'Instruksi pembayaran dibuat.', status: 201);
    }

    public function transaction(Request $request, string $transactionId): JsonResponse
    {
        $transaction = $this->ownedTransaction($request, $transactionId);

        // Pengecekan ulang ke gateway bila pending > 2 menit, di-throttle per transaksi.
        if ($transaction->status->value === 'pending' && $transaction->created_at->lt(now()->subMinutes(2))
            && \Illuminate\Support\Facades\Cache::add('pmb:recheck:'.$transaction->getKey(), true, 60)) {
            rescue(fn () => $this->payments->recheck($transaction, $this->actor($request)), report: false);
            $transaction->refresh();
        }

        return ApiResponse::success($this->presentTransaction($transaction));
    }

    public function cancel(Request $request, string $transactionId): JsonResponse
    {
        return ApiResponse::success($this->presentTransaction($this->payments->cancel($this->ownedTransaction($request, $transactionId))), 'Transaksi dibatalkan.');
    }

    public function manualProof(Request $request, string $transactionId): JsonResponse
    {
        $transaction = $this->ownedTransaction($request, $transactionId);
        $data = $request->validate([
            'file' => ['required', 'file', 'max:2048'],
            'sender_bank' => ['required', 'string', 'max:60'],
            'sender_name' => ['required', 'string', 'max:150'],
            'transfer_date' => ['required', 'date', 'before_or_equal:today'],
            'transferred_amount' => ['required', 'numeric', 'min:1'],
        ]);
        $proof = $this->payments->submitManualProof($transaction, $request->file('file'), $data, $this->actor($request));

        return ApiResponse::success(['id' => $proof->getKey(), 'status' => $proof->status->value], 'Bukti transfer diunggah dan menunggu verifikasi petugas keuangan.', status: 201);
    }

    public function receipt(Request $request, string $invoiceId, PmbFileStorage $storage): JsonResponse
    {
        $file = $this->invoices->receipt($this->invoice($request, $invoiceId));

        return ApiResponse::success(['url' => $storage->temporaryUrl($file)]);
    }

    /** @return array<string, mixed> */
    public static function presentInvoice(PmbInvoice $invoice): array
    {
        return [
            'id' => $invoice->getKey(),
            'invoice_number' => $invoice->invoice_number,
            'type' => $invoice->type->value,
            'status' => $invoice->status->value,
            'amount_gross' => (string) $invoice->amount_gross,
            'amount_discount' => (string) $invoice->amount_discount,
            'amount_net' => (string) $invoice->amount_net,
            'amount_paid' => (string) $invoice->amount_paid,
            'outstanding' => $invoice->outstanding(),
            'minimum_for_enrollment' => $invoice->minimum_for_enrollment !== null ? (string) $invoice->minimum_for_enrollment : null,
            'due_at' => $invoice->due_at?->toIso8601String(),
            'paid_at' => $invoice->paid_at?->toIso8601String(),
            'needs_resolution' => $invoice->needs_resolution,
            'items' => $invoice->relationLoaded('items') ? $invoice->items->map(fn ($i) => ['description' => $i->description, 'code' => $i->code, 'item_type' => $i->item_type, 'amount' => (string) $i->amount, 'installment_seq' => $i->installment_seq, 'due_at' => $i->due_at?->toIso8601String()])->values() : [],
            'transactions' => $invoice->relationLoaded('transactions') ? $invoice->transactions->map(fn ($t) => self::presentTransaction($t))->values() : [],
        ];
    }

    /** @return array<string, mixed> */
    public static function presentTransaction(PaymentTransaction $t): array
    {
        return [
            'id' => $t->getKey(),
            'status' => $t->status->value,
            'channel' => $t->channel,
            'provider' => $t->provider,
            'amount' => (string) $t->amount,
            'unique_code' => $t->unique_code,
            'va_number' => $t->va_number,
            'qr_string' => $t->qr_string,
            'redirect_url' => $t->redirect_url,
            'expires_at' => $t->expires_at?->toIso8601String(),
            'paid_at' => $t->paid_at?->toIso8601String(),
            'receipt_number' => $t->receipt_number,
            'instructions' => $t->instructions,
            'failure_reason' => $t->status->value === 'failed' ? $t->failure_reason : null,
            'manual_proof' => $t->relationLoaded('manualProof') && $t->manualProof ? ['status' => $t->manualProof->status->value, 'reason_code' => $t->manualProof->reason_code] : null,
            'created_at' => $t->created_at?->toIso8601String(),
        ];
    }

    private function invoice(Request $request, string $invoiceId): PmbInvoice
    {
        return PmbInvoice::query()->whereKey($invoiceId)->whereIn('application_id', $this->account($request)->applications()->select('id'))->firstOrFail();
    }

    private function ownedTransaction(Request $request, string $transactionId): PaymentTransaction
    {
        return PaymentTransaction::query()->with('manualProof')->whereKey($transactionId)
            ->whereHas('invoice', fn ($q) => $q->whereIn('application_id', $this->account($request)->applications()->select('id')))->firstOrFail();
    }
}
