<?php

namespace Modules\Finance\Payments;

use Illuminate\Http\Request;
use Modules\Finance\Models\PaymentChannel;
use Modules\Finance\Models\PaymentTransaction;

/**
 * Adapter penyedia pembayaran online KEU (FR-PON-05). Penyedia dapat diganti
 * tanpa mengubah domain KEU. Tidak ada adapter yang dianggap aktif tanpa
 * kredensial (B-KEU-02).
 */
interface PaymentGateway
{
    public function name(): string;

    /** @return array<int, string> metode/bank yang didukung, mis. va_bni, qris */
    public function supportedMethods(): array;

    public function createCharge(PaymentTransaction $transaction, PaymentChannel $channel): ChargeResult;

    public function getStatus(string $providerReference): GatewayStatus;

    public function cancel(string $providerReference): bool;

    /** Verifikasi tanda tangan & parse callback; tanda tangan salah → null. */
    public function verifyWebhook(Request $request): ?WebhookEvent;
}
