<?php

namespace Modules\Admission\Services\Payment;

use App\Support\Tenancy\TenantContext;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Admission\Jobs\ProcessPaymentCallback;
use Modules\Admission\Models\PaymentCallback;
use Modules\Admission\Models\PaymentTransaction;
use Modules\Admission\Payments\GatewayStatus;
use Modules\Admission\Payments\GatewayUnavailable;
use Modules\Admission\Payments\PaymentGatewayManager;
use Modules\Admission\Support\Actor;
use Throwable;

/**
 * Penerimaan webhook gateway (05 §F.4, 11 §A.18.3): endpoint hanya
 * memverifikasi tanda tangan & menyimpan (dedupe unique provider+event),
 * lalu pemrosesan asinkron melakukan double-check ke API gateway.
 */
class WebhookService
{
    private const RETRY_MINUTES = [1, 5, 15, 60, 60, 60];

    public function __construct(
        private readonly PaymentGatewayManager $gateways,
        private readonly PaymentService $payments,
        private readonly TenantContext $tenant,
    ) {}

    /**
     * @return array{status: int, message: string, callback: ?PaymentCallback}
     */
    public function receive(string $provider, Request $request): array
    {
        if (! $this->gateways->has($provider)) {
            return ['status' => 404, 'message' => 'not found', 'callback' => null];
        }

        $event = $this->gateways->gateway($provider)->verifyWebhook($request);
        $headers = collect($request->headers->all())->only(['x-signature', 'x-timestamp', 'user-agent', 'content-type'])->map(fn ($v) => $v[0] ?? null)->all();

        if ($event === null) {
            PaymentCallback::query()->create([
                'provider' => $provider,
                'provider_event_id' => 'invalid-'.hash('sha256', $request->getContent().microtime()),
                'signature_valid' => false,
                'headers' => $headers,
                'payload' => $request->json()->all() ?: ['raw' => mb_substr($request->getContent(), 0, 5000)],
                'received_at' => now(),
                'processed_at' => now(),
                'processing_result' => 'invalid_signature',
            ]);
            Log::warning('[pmb-webhook] tanda tangan tidak valid', ['provider' => $provider, 'ip' => $request->ip()]);

            return ['status' => 401, 'message' => 'invalid signature', 'callback' => null];
        }

        try {
            $callback = PaymentCallback::query()->create([
                'provider' => $provider,
                'provider_event_id' => $event->eventId,
                'signature_valid' => true,
                'headers' => $headers,
                'payload' => $event->payload,
                'received_at' => now(),
            ]);
        } catch (UniqueConstraintViolationException) {
            return ['status' => 200, 'message' => 'already processed', 'callback' => null];
        }

        ProcessPaymentCallback::dispatch($callback->getKey())->afterResponse();

        return ['status' => 200, 'message' => 'accepted', 'callback' => $callback];
    }

    /** Pemrosesan asinkron + retry eksponensial bila API status gateway tidak tersedia. */
    public function process(PaymentCallback $callback): void
    {
        if ($callback->processed_at !== null) {
            return;
        }

        $reference = (string) ($callback->payload['order_id'] ?? $callback->payload['provider_reference'] ?? $callback->payload['external_id'] ?? '');
        $transaction = PaymentTransaction::query()->withoutGlobalScopes()->where('provider', $callback->provider)->where('provider_reference', $reference)->first();

        if ($transaction === null) {
            $callback->forceFill(['processed_at' => now(), 'processing_result' => 'mismatch', 'error_message' => 'Transaksi tidak ditemukan untuk referensi '.$reference])->save();

            return;
        }

        $previousTenant = $this->tenant->universityId();
        $this->tenant->setUniversityId((string) $transaction->university_id);
        $callback->forceFill(['university_id' => $transaction->university_id, 'transaction_id' => $transaction->getKey(), 'attempts' => $callback->attempts + 1])->save();

        try {
            if ($transaction->status->value !== 'pending') {
                $callback->forceFill(['processed_at' => now(), 'processing_result' => 'duplicate_noop'])->save();

                return;
            }

            $status = $this->gateways->gateway($callback->provider)->getStatus($reference);
            $wasExpiredInvoice = in_array($transaction->invoice->status->value, ['expired', 'cancelled'], true);
            $after = $this->payments->applyGatewayStatus($transaction, $status, Actor::system());

            $result = match (true) {
                $after->status->value === 'success' && $wasExpiredInvoice => 'late_payment',
                $after->status->value === 'success' => 'applied',
                $status->status === GatewayStatus::SUCCESS => 'mismatch',
                default => 'applied',
            };

            $callback->forceFill(['processed_at' => now(), 'processing_result' => $result, 'error_message' => null])->save();
        } catch (GatewayUnavailable $e) {
            $attempt = min($callback->attempts, count(self::RETRY_MINUTES)) - 1;
            $callback->forceFill([
                'processing_result' => 'verify_failed',
                'error_message' => $e->getMessage(),
                'next_attempt_at' => now()->addMinutes(self::RETRY_MINUTES[max(0, $attempt)]),
            ])->save();
        } catch (Throwable $e) {
            $callback->forceFill(['processing_result' => 'verify_failed', 'error_message' => mb_substr($e->getMessage(), 0, 1000), 'next_attempt_at' => now()->addMinutes(15)])->save();
            Log::error('[pmb-webhook] pemrosesan gagal', ['callback' => $callback->getKey(), 'error' => $e->getMessage()]);
        } finally {
            $this->tenant->setUniversityId($previousTenant);
        }
    }

    /** Scheduler: callback yang verifikasinya tertunda (gateway sempat tidak tersedia). */
    public function retryPending(): int
    {
        $count = 0;

        PaymentCallback::query()->whereNull('processed_at')->where('signature_valid', true)->where('attempts', '<', 6)
            ->where(fn ($q) => $q->whereNull('next_attempt_at')->orWhere('next_attempt_at', '<=', now()))
            ->where('received_at', '<', now()->subMinute())
            ->limit(200)->get()
            ->each(function (PaymentCallback $callback) use (&$count): void {
                $this->process($callback);
                $count++;
            });

        return $count;
    }
}
