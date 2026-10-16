<?php

namespace Modules\Finance\Services;

use App\Models\User;
use App\Support\Http\Exceptions\ConflictException;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Academic\Models\Student;
use Modules\Finance\Enums\ChannelCategory;
use Modules\Finance\Enums\PaymentMethod;
use Modules\Finance\Enums\ReconciliationStatus;
use Modules\Finance\Enums\TransactionStatus;
use Modules\Finance\Models\PaymentCallback;
use Modules\Finance\Models\PaymentChannel;
use Modules\Finance\Models\PaymentTransaction;
use Modules\Finance\Payments\GatewayRegistry;
use Modules\Finance\Payments\GatewayStatus;
use Modules\Finance\Support\FinanceNumbering;
use Modules\Finance\Support\Money;
use Throwable;

/**
 * Pembayaran online (F-09): buat transaksi via adapter penyedia, terima
 * callback (tanda tangan + dedupe + cek ulang status, BR-KEU-41/44),
 * polling cadangan, dan kedaluwarsa. Status dari front-end tidak pernah
 * mengubah transaksi — hanya memicu cek ulang (`recheck`).
 */
class OnlinePaymentService
{
    public function __construct(
        private readonly GatewayRegistry $gateways,
        private readonly ChannelService $channels,
        private readonly TargetResolver $targets,
        private readonly PaymentPostingService $posting,
        private readonly FinanceNumbering $numbering,
    ) {}

    public function create(Student $student, PaymentChannel $channel, string $targetType, ?string $targetId, string $idempotencyKey, User $actor): PaymentTransaction
    {
        $existing = PaymentTransaction::query()->where('idempotency_key', $idempotencyKey)->first();

        if ($existing !== null) {
            if ($existing->student_id !== $student->id || $existing->payment_channel_id !== $channel->id || $existing->target_id !== $targetId) {
                throw new ConflictException('Idempotency-Key sudah dipakai untuk permintaan berbeda.', 'IDEMPOTENCY_MISMATCH');
            }

            return $existing;
        }

        if ($channel->category === ChannelCategory::Offline) {
            throw new ConflictException('Kanal ini bukan kanal online.');
        }

        if (! $this->channels->isUsable($channel)) {
            throw new ConflictException('Kanal pembayaran ini belum aktif atau belum terhubung ke penyedia. Gunakan kanal lain atau transfer manual.', 'CHANNEL_NOT_CONNECTED');
        }

        [$type, $id, $outstanding] = $this->targets->resolve($student, $targetType, $targetId);

        if ($type === PaymentTransaction::TARGET_DEPOSIT || $outstanding <= 0) {
            throw new ConflictException('Pembayaran online hanya untuk tagihan atau termin yang belum lunas.');
        }

        $this->cancelPendingFor($id);
        $fee = $channel->fee_bearer === 'student' ? Money::toCents($channel->fee_amount) : 0;

        try {
            $tx = PaymentTransaction::query()->create([
                'university_id' => $student->university_id,
                'transaction_number' => $this->numbering->next('transaction', null, $student->university_id),
                'student_id' => $student->id,
                'payment_channel_id' => $channel->id,
                'provider' => $channel->provider,
                'method' => PaymentMethod::Online,
                'target_type' => $type,
                'target_id' => $id,
                'amount' => Money::fromCents($outstanding + $fee),
                'channel_fee' => Money::fromCents($fee),
                'status' => TransactionStatus::Created,
                'idempotency_key' => $idempotencyKey,
                'recorded_by' => $actor->id,
                'reconciliation_status' => ReconciliationStatus::Unreconciled,
            ]);
        } catch (UniqueConstraintViolationException) {
            return PaymentTransaction::query()->where('idempotency_key', $idempotencyKey)->firstOrFail();
        }

        try {
            $charge = $this->gateways->get($channel->provider)->createCharge($tx, $channel);
        } catch (Throwable $e) {
            $tx->forceFill(['status' => TransactionStatus::Failed, 'failure_reason' => mb_substr($e->getMessage(), 0, 255)])->save();

            throw $e;
        }

        $tx->forceFill([
            'status' => TransactionStatus::Pending,
            'provider_reference' => $charge->providerReference,
            'va_number' => $charge->vaNumber,
            'qr_string' => $charge->qrString,
            'redirect_url' => $charge->redirectUrl,
            'instructions' => ['steps' => $charge->instructions, 'channel_instructions' => $channel->instructions],
            'expires_at' => $charge->expiresAt ? CarbonImmutable::parse($charge->expiresAt) : now()->addMinutes($channel->expiry_minutes),
            'next_recheck_at' => now()->addMinutes(15),
        ])->save();

        return $tx;
    }

    public function cancel(PaymentTransaction $tx): PaymentTransaction
    {
        if (! in_array($tx->status, [TransactionStatus::Created, TransactionStatus::Pending], true)) {
            throw new ConflictException('Hanya transaksi yang belum dibayar yang dapat dibatalkan.');
        }

        if ($tx->method === PaymentMethod::Online && $tx->provider_reference !== null && $this->gateways->has($tx->provider)) {
            $this->gateways->get($tx->provider)->cancel($tx->provider_reference);
        }

        $tx->forceFill(['status' => TransactionStatus::Cancelled])->save();

        return $tx;
    }

    /**
     * Endpoint webhook: simpan callback apa adanya, verifikasi tanda tangan,
     * dedupe, lalu proses (cek ulang ke penyedia).
     *
     * @return array{status: int, message: string}
     */
    public function handleWebhook(string $provider, Request $request): array
    {
        if (! $this->gateways->has($provider)) {
            return ['status' => 404, 'message' => 'Penyedia tidak dikenal.'];
        }

        $event = $this->gateways->get($provider)->verifyWebhook($request);

        if ($event === null) {
            PaymentCallback::query()->create([
                'provider' => $provider,
                'provider_event_id' => 'invalid-'.hash('sha256', $request->getContent().microtime()),
                'signature_valid' => false,
                'payload' => $request->json()->all() ?: ['raw' => mb_substr($request->getContent(), 0, 2000)],
                'received_at' => now(),
                'processing_result' => 'invalid_signature',
            ]);

            return ['status' => 401, 'message' => 'Tanda tangan tidak valid.'];
        }

        try {
            $callback = PaymentCallback::query()->create([
                'provider' => $provider,
                'provider_event_id' => $event->eventId,
                'signature_valid' => true,
                'payload' => $event->payload,
                'received_at' => now(),
            ]);
        } catch (UniqueConstraintViolationException) {
            return ['status' => 200, 'message' => 'Sudah diproses.'];
        }

        $tx = PaymentTransaction::query()->withoutGlobalScopes()->where('provider', $provider)->where('provider_reference', $event->providerReference)->first()
            ?? PaymentTransaction::query()->withoutGlobalScopes()->where('transaction_number', $event->providerReference)->first();

        if ($tx === null) {
            $callback->forceFill(['processed_at' => now(), 'processing_result' => 'unknown_transaction'])->save();

            return ['status' => 200, 'message' => 'Transaksi tidak dikenal; dicatat untuk rekonsiliasi.'];
        }

        // Webhook tidak membawa konteks tenant: aktifkan tenant milik transaksi.
        app(TenantContext::class)->setUniversityId($tx->university_id);
        $callback->forceFill(['university_id' => $tx->university_id, 'payment_transaction_id' => $tx->id])->save();

        try {
            $result = $this->recheck($tx);
            $callback->forceFill(['processed_at' => now(), 'processing_result' => $result])->save();
        } catch (Throwable $e) {
            $callback->forceFill(['processing_result' => 'error', 'error_message' => mb_substr($e->getMessage(), 0, 1000)])->save();
        }

        return ['status' => 200, 'message' => 'Diterima.'];
    }

    /** Cek ulang status ke penyedia (double-check) dan terapkan hasilnya. */
    public function recheck(PaymentTransaction $tx): string
    {
        if ($tx->method !== PaymentMethod::Online || $tx->provider_reference === null) {
            return 'not_online';
        }

        if ($tx->status === TransactionStatus::Success) {
            return 'already_success';
        }

        $status = $this->gateways->get($tx->provider)->getStatus($tx->provider_reference);
        $tx->forceFill(['recheck_attempts' => $tx->recheck_attempts + 1, 'next_recheck_at' => now()->addMinutes(15)])->save();

        if ($status->status === GatewayStatus::SUCCESS) {
            if ($status->amount !== null && Money::compare($status->amount, $tx->amount) !== 0) {
                $tx->forceFill(['reconciliation_status' => ReconciliationStatus::Exception, 'failure_reason' => 'Nominal dari penyedia ('.$status->amount.') berbeda dengan transaksi.'])->save();

                return 'amount_mismatch';
            }

            $paidAt = $status->paidAt ? CarbonImmutable::parse($status->paidAt) : CarbonImmutable::now();
            $this->posting->settle($tx, (string) $tx->amount, $paidAt);

            return 'success';
        }

        $map = [GatewayStatus::FAILED => TransactionStatus::Failed, GatewayStatus::EXPIRED => TransactionStatus::Expired, GatewayStatus::CANCELLED => TransactionStatus::Cancelled];

        if (isset($map[$status->status]) && in_array($tx->status, [TransactionStatus::Pending, TransactionStatus::Created], true)) {
            $tx->forceFill(['status' => $map[$status->status]])->save();

            return $status->status;
        }

        return 'pending';
    }

    /** Transaksi pending yang lewat masa berlaku → expired (job). */
    public function expireStale(): int
    {
        $count = 0;

        PaymentTransaction::query()->whereIn('status', [TransactionStatus::Pending, TransactionStatus::Created])
            ->whereNotNull('expires_at')->where('expires_at', '<', now())
            ->chunkById(200, function ($rows) use (&$count): void {
                foreach ($rows as $tx) {
                    $tx->forceFill(['status' => TransactionStatus::Expired])->save();
                    $count++;
                }
            });

        return $count;
    }

    private function cancelPendingFor(?string $targetId): void
    {
        PaymentTransaction::query()->where('target_id', $targetId)->whereIn('status', [TransactionStatus::Pending, TransactionStatus::Created])
            ->where('method', PaymentMethod::Online)->get()
            ->each(function (PaymentTransaction $tx): void {
                try {
                    $this->cancel($tx);
                } catch (Throwable) {
                    // Pembatalan di penyedia gagal: transaksi tetap dipantau; callback sukses nanti → kelebihan ke deposit.
                }
            });

        DB::table('payment_transactions')->where('target_id', $targetId)->where('status', TransactionStatus::Pending->value)
            ->where('method', PaymentMethod::ManualTransfer->value)->update(['status' => TransactionStatus::Cancelled->value, 'updated_at' => now()]);
    }
}
