<?php

namespace Modules\Admission\Payments;

use Illuminate\Http\Request;
use Modules\Admission\Models\PaymentTransaction;

/**
 * Adapter penyedia pembayaran (docs/pmb/11 §B.3). Penyedia dapat diganti
 * tanpa mengubah domain PMB. Kegagalan jaringan → GatewayUnavailable.
 */
interface PaymentGatewayContract
{
    public function name(): string;

    /** @return array<int, string> kanal yang didukung adapter (mis. va_bni, qris). */
    public function supportedChannels(): array;

    public function createCharge(PaymentTransaction $transaction, string $channel, string $amount, int $expiryHours): ChargeResult;

    public function getStatus(string $providerReference): GatewayStatus;

    public function cancel(string $providerReference): bool;

    /** @throws GatewayUnsupported bila refund via API tidak didukung kanal/penyedia. */
    public function refund(string $providerReference, string $amount, string $reason): bool;

    /** Memverifikasi tanda tangan & mem-parse webhook. Tanda tangan salah → null. */
    public function verifyWebhook(Request $request): ?WebhookEvent;

    /**
     * @return array<int, array{reference: string, amount: string, paid_at: ?string, status: string}>
     *
     * @throws GatewayUnsupported
     */
    public function fetchSettlement(string $date): array;
}
