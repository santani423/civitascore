<?php

namespace Modules\Admission\Payments;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Modules\Admission\Models\PaymentTransaction;
use Modules\Admission\Support\Money;
use Throwable;

/**
 * Adapter Midtrans Core API: Virtual Account bank & QRIS. Tanda tangan
 * notifikasi = SHA512(order_id + status_code + gross_amount + server_key);
 * status selalu di-double-check lewat GET /v2/{order_id}/status sebelum
 * transaksi ditandai sukses (05 §F.4).
 *
 * `order_id` = ID transaksi PMB (ULID) sehingga provider_reference unik.
 * Midtrans tidak menyediakan API settlement harian publik → rekonsiliasi
 * memakai impor mutasi/laporan (fetchSettlement melempar GatewayUnsupported).
 */
class MidtransGateway implements PaymentGatewayContract
{
    private const BANK_CHANNELS = ['va_bni' => 'bni', 'va_bri' => 'bri', 'va_bca' => 'bca', 'va_permata' => 'permata', 'va_cimb' => 'cimb'];

    public function __construct(private readonly string $serverKey, private readonly bool $production) {}

    public function name(): string
    {
        return 'midtrans';
    }

    public function supportedChannels(): array
    {
        return [...array_keys(self::BANK_CHANNELS), 'va_mandiri', 'qris'];
    }

    public function createCharge(PaymentTransaction $transaction, string $channel, string $amount, int $expiryHours): ChargeResult
    {
        $grossAmount = (int) round(Money::toCents($amount) / 100);
        $body = [
            'transaction_details' => ['order_id' => (string) $transaction->getKey(), 'gross_amount' => $grossAmount],
            'custom_expiry' => ['expiry_duration' => $expiryHours, 'unit' => 'hour'],
        ];

        $body += match (true) {
            isset(self::BANK_CHANNELS[$channel]) => ['payment_type' => 'bank_transfer', 'bank_transfer' => ['bank' => self::BANK_CHANNELS[$channel]]],
            $channel === 'va_mandiri' => ['payment_type' => 'echannel', 'echannel' => ['bill_info1' => 'PMB', 'bill_info2' => 'Biaya']],
            $channel === 'qris' => ['payment_type' => 'qris', 'qris' => ['acquirer' => 'gopay']],
            default => throw new GatewayUnsupported("Kanal {$channel} tidak didukung Midtrans."),
        };

        $response = $this->send(fn (PendingRequest $http) => $http->post($this->baseUrl().'/v2/charge', $body));
        $data = $response->json() ?? [];

        if (! in_array((string) ($data['status_code'] ?? ''), ['200', '201'], true)) {
            throw new GatewayUnavailable('Penyedia pembayaran menolak permintaan: '.($data['status_message'] ?? 'tidak diketahui').'.');
        }

        $va = $data['va_numbers'][0]['va_number'] ?? $data['permata_va_number'] ?? null;

        if ($channel === 'va_mandiri' && isset($data['bill_key'])) {
            $va = ($data['biller_code'] ?? '').'-'.$data['bill_key'];
        }

        $qrUrl = collect($data['actions'] ?? [])->firstWhere('name', 'generate-qr-code')['url'] ?? null;

        return new ChargeResult(
            providerReference: (string) ($data['order_id'] ?? $transaction->getKey()),
            vaNumber: $va,
            qrString: $data['qr_string'] ?? null,
            redirectUrl: $qrUrl,
            expiresAt: $data['expiry_time'] ?? null,
            instructions: $this->instructions($channel, $va),
            rawStatus: $data['transaction_status'] ?? null,
        );
    }

    public function getStatus(string $providerReference): GatewayStatus
    {
        $data = $this->send(fn (PendingRequest $http) => $http->get($this->baseUrl().'/v2/'.rawurlencode($providerReference).'/status'))->json() ?? [];

        if ((string) ($data['status_code'] ?? '') === '404') {
            return new GatewayStatus(GatewayStatus::PENDING, null, raw: 'not_found');
        }

        $raw = (string) ($data['transaction_status'] ?? 'pending');
        $status = match ($raw) {
            'settlement', 'capture' => ($data['fraud_status'] ?? 'accept') === 'accept' ? GatewayStatus::SUCCESS : GatewayStatus::PENDING,
            'expire' => GatewayStatus::EXPIRED,
            'cancel' => GatewayStatus::CANCELLED,
            'deny', 'failure' => GatewayStatus::FAILED,
            'refund', 'partial_refund' => GatewayStatus::REFUNDED,
            default => GatewayStatus::PENDING,
        };

        return new GatewayStatus($status, isset($data['gross_amount']) ? Money::fromCents(Money::toCents((string) $data['gross_amount'])) : null, (string) ($data['currency'] ?? 'IDR'), $data['settlement_time'] ?? $data['transaction_time'] ?? null, $raw);
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

    public function refund(string $providerReference, string $amount, string $reason): bool
    {
        $data = $this->send(fn (PendingRequest $http) => $http->post($this->baseUrl().'/v2/'.rawurlencode($providerReference).'/refund', [
            'refund_key' => 'rf-'.$providerReference.'-'.substr(sha1($amount.$reason), 0, 8),
            'amount' => (int) round(Money::toCents($amount) / 100),
            'reason' => mb_substr($reason, 0, 100),
        ]))->json() ?? [];

        if ((string) ($data['status_code'] ?? '') === '412') {
            throw new GatewayUnsupported('Refund via API tidak didukung untuk transaksi ini.');
        }

        return (string) ($data['status_code'] ?? '') === '200';
    }

    public function verifyWebhook(Request $request): ?WebhookEvent
    {
        $payload = $request->json()->all();
        $orderId = (string) ($payload['order_id'] ?? '');
        $statusCode = (string) ($payload['status_code'] ?? '');
        $gross = (string) ($payload['gross_amount'] ?? '');
        $signature = (string) ($payload['signature_key'] ?? '');

        if ($orderId === '' || $signature === '' || $this->serverKey === '') {
            return null;
        }

        $expected = hash('sha512', $orderId.$statusCode.$gross.$this->serverKey);

        if (! hash_equals($expected, $signature)) {
            return null;
        }

        $status = (string) ($payload['transaction_status'] ?? 'pending');
        // Midtrans tidak mengirim event id: hash(reference + status + waktu) (05 §F.4 aturan 2).
        $eventId = hash('sha256', $orderId.'|'.$status.'|'.($payload['settlement_time'] ?? $payload['transaction_time'] ?? ''));

        return new WebhookEvent($eventId, $orderId, $status, $payload);
    }

    public function fetchSettlement(string $date): array
    {
        throw new GatewayUnsupported('Midtrans tidak menyediakan API settlement; gunakan impor laporan/mutasi.');
    }

    private function baseUrl(): string
    {
        return $this->production ? 'https://api.midtrans.com' : 'https://api.sandbox.midtrans.com';
    }

    /**
     * @param  callable(PendingRequest): Response  $call
     */
    private function send(callable $call): Response
    {
        try {
            $response = $call(Http::withBasicAuth($this->serverKey, '')->acceptJson()->asJson()->timeout((int) config('pmb.payment.gateway_timeout_seconds', 10)));
        } catch (ConnectionException) {
            throw new GatewayUnavailable;
        } catch (Throwable $e) {
            throw new GatewayUnavailable;
        }

        if ($response->serverError()) {
            throw new GatewayUnavailable;
        }

        return $response;
    }

    /** @return array<int, array{title: string, steps: array<int, string>}> */
    private function instructions(string $channel, ?string $va): array
    {
        if ($channel === 'qris') {
            return [['title' => 'QRIS', 'steps' => ['Buka aplikasi bank/e-wallet yang mendukung QRIS.', 'Pindai kode QR yang ditampilkan.', 'Periksa nominal lalu konfirmasi pembayaran.']]];
        }

        $bank = strtoupper(str_replace('va_', '', $channel));

        return [
            ['title' => "ATM {$bank}", 'steps' => ['Pilih menu Transfer / Pembayaran Virtual Account.', "Masukkan nomor Virtual Account {$va}.", 'Periksa nama & nominal, lalu konfirmasi.']],
            ['title' => "Mobile Banking {$bank}", 'steps' => ['Masuk ke aplikasi mobile banking.', "Pilih Virtual Account dan masukkan {$va}.", 'Konfirmasi pembayaran.']],
        ];
    }
}
