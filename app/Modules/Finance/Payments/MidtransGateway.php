<?php

namespace Modules\Finance\Payments;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Modules\Finance\Models\PaymentChannel;
use Modules\Finance\Models\PaymentTransaction;
use Modules\Finance\Support\Money;
use Throwable;

/**
 * Adapter Midtrans Core API untuk KEU (VA bank & QRIS). Semantik sama dengan
 * adapter PMB yang sudah diuji dengan respons tiruan: tanda tangan
 * SHA512(order_id + status_code + gross_amount + server_key), status selalu
 * dicek ulang lewat GET /v2/{order_id}/status. `order_id` = nomor transaksi
 * KEU. Aktif hanya bila KEU_MIDTRANS_SERVER_KEY diisi; belum diuji terhadap
 * sandbox/produksi Midtrans (butuh kredensial kampus).
 */
class MidtransGateway implements PaymentGateway
{
    private const BANKS = ['bni', 'bri', 'bca', 'permata', 'cimb'];

    public function __construct(private readonly string $serverKey, private readonly bool $production, private readonly int $timeout = 10) {}

    public function name(): string
    {
        return 'midtrans';
    }

    public function supportedMethods(): array
    {
        return [...array_map(fn ($b) => 'va_'.$b, self::BANKS), 'va_mandiri', 'qris'];
    }

    public function createCharge(PaymentTransaction $transaction, PaymentChannel $channel): ChargeResult
    {
        $code = $channel->method === 'va' ? 'va_'.strtolower((string) $channel->bank_code) : $channel->method;
        $body = [
            'transaction_details' => ['order_id' => $transaction->transaction_number, 'gross_amount' => (int) round(Money::toCents($transaction->amount) / 100)],
            'custom_expiry' => ['expiry_duration' => max(1, (int) ceil($channel->expiry_minutes / 60)), 'unit' => 'hour'],
        ];

        $body += match (true) {
            in_array(substr($code, 3), self::BANKS, true) => ['payment_type' => 'bank_transfer', 'bank_transfer' => ['bank' => substr($code, 3)]],
            $code === 'va_mandiri' => ['payment_type' => 'echannel', 'echannel' => ['bill_info1' => 'Tagihan', 'bill_info2' => 'Kuliah']],
            $code === 'qris' => ['payment_type' => 'qris', 'qris' => ['acquirer' => 'gopay']],
            default => throw new GatewayUnavailable("Metode {$code} tidak didukung Midtrans."),
        };

        // Satu akun Midtrans hanya punya satu URL notifikasi di dashboard (dipakai PMB); KEU menimpanya per transaksi.
        $notifyUrl = url('/api/v1/finance/webhooks/payments/'.$this->name());
        $data = $this->send(fn (PendingRequest $http) => $http->withHeaders(['X-Override-Notification' => $notifyUrl])->post($this->baseUrl().'/v2/charge', $body))->json() ?? [];

        if (! in_array((string) ($data['status_code'] ?? ''), ['200', '201'], true)) {
            throw new GatewayUnavailable('Penyedia pembayaran menolak permintaan: '.($data['status_message'] ?? 'tidak diketahui').'.');
        }

        $va = $data['va_numbers'][0]['va_number'] ?? $data['permata_va_number'] ?? null;

        if ($code === 'va_mandiri' && isset($data['bill_key'])) {
            $va = ($data['biller_code'] ?? '').'-'.$data['bill_key'];
        }

        return new ChargeResult(
            providerReference: (string) ($data['order_id'] ?? $transaction->transaction_number),
            vaNumber: $va,
            qrString: $data['qr_string'] ?? null,
            redirectUrl: collect($data['actions'] ?? [])->firstWhere('name', 'generate-qr-code')['url'] ?? null,
            expiresAt: $data['expiry_time'] ?? null,
        );
    }

    public function getStatus(string $providerReference): GatewayStatus
    {
        $data = $this->send(fn (PendingRequest $http) => $http->get($this->baseUrl().'/v2/'.rawurlencode($providerReference).'/status'))->json() ?? [];

        if ((string) ($data['status_code'] ?? '') === '404') {
            return new GatewayStatus(GatewayStatus::PENDING, null);
        }

        $status = match ((string) ($data['transaction_status'] ?? 'pending')) {
            'settlement', 'capture' => ($data['fraud_status'] ?? 'accept') === 'accept' ? GatewayStatus::SUCCESS : GatewayStatus::PENDING,
            'expire' => GatewayStatus::EXPIRED,
            'cancel' => GatewayStatus::CANCELLED,
            'deny', 'failure' => GatewayStatus::FAILED,
            default => GatewayStatus::PENDING,
        };

        return new GatewayStatus($status, isset($data['gross_amount']) ? Money::normalize((string) $data['gross_amount']) : null, $data['settlement_time'] ?? $data['transaction_time'] ?? null);
    }

    public function cancel(string $providerReference): bool
    {
        try {
            $data = $this->send(fn (PendingRequest $http) => $http->post($this->baseUrl().'/v2/'.rawurlencode($providerReference).'/cancel'))->json() ?? [];

            return in_array((string) ($data['status_code'] ?? ''), ['200', '412'], true);
        } catch (GatewayUnavailable) {
            return false;
        }
    }

    public function verifyWebhook(Request $request): ?WebhookEvent
    {
        $payload = $request->json()->all();
        $orderId = (string) ($payload['order_id'] ?? '');
        $signature = (string) ($payload['signature_key'] ?? '');

        if ($orderId === '' || $signature === '' || $this->serverKey === '') {
            return null;
        }

        $expected = hash('sha512', $orderId.($payload['status_code'] ?? '').($payload['gross_amount'] ?? '').$this->serverKey);

        if (! hash_equals($expected, $signature)) {
            return null;
        }

        $eventId = hash('sha256', $orderId.'|'.($payload['transaction_status'] ?? '').'|'.($payload['settlement_time'] ?? $payload['transaction_time'] ?? ''));

        return new WebhookEvent($eventId, $orderId, $payload);
    }

    private function baseUrl(): string
    {
        return $this->production ? 'https://api.midtrans.com' : 'https://api.sandbox.midtrans.com';
    }

    /** @param  callable(PendingRequest): Response  $call */
    private function send(callable $call): Response
    {
        try {
            $response = $call(Http::withBasicAuth($this->serverKey, '')->acceptJson()->asJson()->timeout($this->timeout));
        } catch (Throwable) {
            throw new GatewayUnavailable;
        }

        if ($response->serverError()) {
            throw new GatewayUnavailable;
        }

        return $response;
    }
}
