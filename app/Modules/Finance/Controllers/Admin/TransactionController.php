<?php

namespace Modules\Finance\Controllers\Admin;

use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Academic\Models\Student;
use Modules\FileManagement\Support\FileAttacher;
use Modules\Finance\Controllers\KeuController;
use Modules\Finance\Enums\CashierSessionStatus;
use Modules\Finance\Enums\ProofStatus;
use Modules\Finance\Models\CashierSession;
use Modules\Finance\Models\PaymentProof;
use Modules\Finance\Models\PaymentTransaction;
use Modules\Finance\Services\CashierService;
use Modules\Finance\Services\ManualPaymentService;
use Modules\Finance\Services\OnlinePaymentService;
use Modules\Finance\Services\PaymentPostingService;
use Modules\Finance\Support\Presenter;

/** Transaksi pembayaran, verifikasi bukti transfer, dan loket/kasir (F-09/F-10/F-13). */
class TransactionController extends KeuController
{
    public function index(Request $request): JsonResponse
    {
        $query = PaymentTransaction::query()->with(['student', 'channel', 'proof'])->latest()
            ->when($request->filled('status'), fn ($q) => $q->whereIn('status', explode(',', $request->string('status'))))
            ->when($request->filled('method'), fn ($q) => $q->where('method', $request->string('method')))
            ->when($request->filled('reconciliation_status'), fn ($q) => $q->where('reconciliation_status', $request->string('reconciliation_status')))
            ->when($request->filled('student_id'), fn ($q) => $q->where('student_id', $request->string('student_id')))
            ->when($request->filled('cashier_session_id'), fn ($q) => $q->where('cashier_session_id', $request->string('cashier_session_id')))
            ->when($request->boolean('needs_resolution'), fn ($q) => $q->where('needs_resolution', true))
            // Rentang tanggal = tanggal bayar efektif (transaksi belum dibayar: tanggal dibuat).
            ->when($request->filled('date_from'), fn ($q) => $q->whereRaw('COALESCE(paid_at, created_at) >= ?', [$request->string('date_from').' 00:00:00']))
            ->when($request->filled('date_to'), fn ($q) => $q->whereRaw('COALESCE(paid_at, created_at) <= ?', [$request->string('date_to').' 23:59:59']))
            ->when($request->filled('search'), function ($q) use ($request): void {
                $term = '%'.$request->string('search').'%';
                $q->where(fn ($w) => $w->where('transaction_number', 'like', $term)->orWhere('receipt_number', 'like', $term)->orWhere('provider_reference', 'like', $term)
                    ->orWhereIn('student_id', Student::query()->select('id')->where(fn ($s) => $s->where('nim', 'like', $term)->orWhere('name', 'like', $term))));
            });

        return $this->paginate($query, $request, fn (PaymentTransaction $t) => Presenter::transaction($t));
    }

    public function show(PaymentTransaction $paymentTransaction): JsonResponse
    {
        return ApiResponse::success(Presenter::transaction($paymentTransaction->load(['student.studyProgram', 'channel', 'proof', 'allocations', 'recorder', 'verifier'])));
    }

    public function recheck(PaymentTransaction $paymentTransaction, OnlinePaymentService $online): JsonResponse
    {
        $result = $online->recheck($paymentTransaction);

        return ApiResponse::success(Presenter::transaction($paymentTransaction->fresh()), "Hasil cek ulang: {$result}.");
    }

    public function cancel(PaymentTransaction $paymentTransaction, OnlinePaymentService $online): JsonResponse
    {
        return ApiResponse::success(Presenter::transaction($online->cancel($paymentTransaction)), 'Transaksi dibatalkan.');
    }

    public function reverse(Request $request, PaymentTransaction $paymentTransaction, PaymentPostingService $posting): JsonResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:255']]);

        return ApiResponse::success(Presenter::transaction($posting->reverse($paymentTransaction, $data['reason'], $this->user($request))), 'Transaksi dibalik; tagihan dihitung ulang.');
    }

    public function proofs(Request $request): JsonResponse
    {
        $status = $request->string('status')->toString() ?: ProofStatus::Submitted->value;
        $query = PaymentProof::query()->with(['transaction.student', 'reviewer'])->where('status', $status)
            ->orderBy($status === ProofStatus::Submitted->value ? 'created_at' : 'reviewed_at', $status === ProofStatus::Submitted->value ? 'asc' : 'desc');

        return $this->paginate($query, $request, fn (PaymentProof $p) => [
            ...Presenter::transaction($p->transaction->setRelation('proof', $p)),
            'proof_id' => $p->id,
            'reviewed_by' => $p->reviewer?->name,
            'reviewed_at' => $p->reviewed_at?->toIso8601String(),
            'submitted_at' => $p->created_at?->toIso8601String(),
        ]);
    }

    public function approveProof(Request $request, PaymentProof $paymentProof, ManualPaymentService $manual): JsonResponse
    {
        $data = $request->validate(['amount' => ['nullable', 'numeric', 'gt:0'], 'note' => ['nullable', 'string', 'max:1000']]);
        $tx = $manual->approve($paymentProof, $this->user($request), isset($data['amount']) ? (string) $data['amount'] : null, $data['note'] ?? null);

        return ApiResponse::success(Presenter::transaction($tx->load(['allocations', 'proof'])), 'Bukti disetujui; pembayaran dicatat.');
    }

    public function rejectProof(Request $request, PaymentProof $paymentProof, ManualPaymentService $manual): JsonResponse
    {
        $data = $request->validate([
            'reason_code' => ['required', Rule::in(array_keys(ManualPaymentService::REJECT_REASONS))],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);
        $manual->reject($paymentProof, $this->user($request), $data['reason_code'], $data['note'] ?? null);

        return ApiResponse::success(null, 'Bukti ditolak dan mahasiswa diberi tahu.');
    }

    public function sessions(Request $request): JsonResponse
    {
        $user = $this->user($request);
        $query = CashierSession::query()->with(['cashier', 'verifier'])->latest('opened_at')
            ->when(! $user->hasPermissionTo('cashier_sessions.approve'), fn ($q) => $q->where('cashier_id', $user->id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')));

        return $this->paginate($query, $request, fn (CashierSession $s) => $this->presentSession($s));
    }

    public function openSession(Request $request, CashierService $cashier): JsonResponse
    {
        $data = $request->validate(['opening_float' => ['required', 'numeric', 'min:0']]);

        return ApiResponse::success($this->presentSession($cashier->open($this->user($request), (string) $data['opening_float'])), 'Sesi kas dibuka.', status: 201);
    }

    public function recordCash(Request $request, CashierSession $cashierSession, CashierService $cashier): JsonResponse
    {
        $data = $request->validate([
            'student_id' => ['required', 'string'],
            'target_type' => ['required', Rule::in(['invoice', 'installment', 'deposit'])],
            'target_id' => ['nullable', 'required_unless:target_type,deposit', 'string'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'payer_name' => ['nullable', 'string', 'max:150'],
        ]);
        $tx = $cashier->record($cashierSession, Student::query()->findOrFail($data['student_id']), $data['target_type'], $data['target_id'] ?? null, (string) $data['amount'], $data['payer_name'] ?? null, $this->user($request));

        return ApiResponse::success(Presenter::transaction($tx->load('allocations')), 'Pembayaran tunai dicatat. Kuitansi: '.$tx->receipt_number, status: 201);
    }

    public function closeSession(Request $request, CashierSession $cashierSession, CashierService $cashier): JsonResponse
    {
        $data = $request->validate(['counted_total' => ['required', 'numeric', 'min:0'], 'note' => ['nullable', 'string', 'max:1000']]);

        return ApiResponse::success($this->presentSession($cashier->close($cashierSession, (string) $data['counted_total'], $data['note'] ?? null, $this->user($request))), 'Sesi kas ditutup.');
    }

    public function depositSession(Request $request, CashierSession $cashierSession, CashierService $cashier, FileAttacher $files): JsonResponse
    {
        $data = $request->validate(['deposit_reference' => ['required', 'string', 'max:100'], 'deposit_file_id' => ['nullable', 'string']]);
        $file = $files->resolve($data['deposit_file_id'] ?? null, 'deposit_file_id', $this->user($request), null, ['pdf', 'jpg', 'jpeg', 'png'], 5);
        $session = $cashier->recordDeposit($cashierSession, $data['deposit_reference'], $file?->id, $this->user($request));
        $files->attach($file, $session);

        return ApiResponse::success($this->presentSession($session), 'Bukti setoran dicatat.');
    }

    public function verifySession(Request $request, CashierSession $cashierSession, CashierService $cashier): JsonResponse
    {
        $data = $request->validate(['note' => ['nullable', 'string', 'max:1000']]);

        return ApiResponse::success($this->presentSession($cashier->verify($cashierSession, $this->user($request), $data['note'] ?? null)), 'Setoran sesi kas diverifikasi.');
    }

    /** @return array<string, mixed> */
    private function presentSession(CashierSession $s): array
    {
        return [
            'id' => $s->id, 'session_number' => $s->session_number, 'cashier' => $s->relationLoaded('cashier') ? $s->cashier?->name : null, 'cashier_id' => $s->cashier_id,
            'opened_at' => $s->opened_at?->toIso8601String(), 'closed_at' => $s->closed_at?->toIso8601String(), 'opening_float' => (string) $s->opening_float,
            'system_total' => (string) $s->system_total, 'counted_total' => $s->counted_total !== null ? (string) $s->counted_total : null,
            'difference' => $s->difference !== null ? (string) $s->difference : null, 'deposit_reference' => $s->deposit_reference, 'deposit_file_id' => $s->deposit_file_id,
            'status' => $s->status->value, 'status_label' => $s->status->label(), 'verified_by' => $s->relationLoaded('verifier') ? $s->verifier?->name : null,
            'note' => $s->note, 'is_open' => $s->status === CashierSessionStatus::Open,
        ];
    }
}
