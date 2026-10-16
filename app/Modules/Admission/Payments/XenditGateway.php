<?php

namespace Modules\Admission\Payments;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Modules\Admission\Models\PaymentTransaction;
use Modules\Admission\Support\Money;
use Throwable;

/**
 * Adapter Xendit Invoice API: VA bank, QRIS, e-wallet & gerai ritel. Satu
 * transaksi = satu invoice Xendit yang dibatasi ke kanal pilihan
 * (`payment_methods`); pendaftar membayar lewat halaman checkout Xendit
 * (`invoice_url`). Callback diverifikasi dengan header `x-callback-token`
 * (XENDIT_WEBHOOK_TOKEN) dan status selalu di-double-check lewat
 * GET /v2/invoices/{id} sebelum transaksi ditandai sukses (05 §F.4).
 *
 * `provider_reference` = ID invoice Xendit. `external_id` = "PMB-" + ID
 * transaksi sehingga callback dari satu akun Xendit yang dipakai bersama
 * KEU dapat diarahkan ke modul yang benar (App\Http\Controllers\XenditWebhookController).
 * API settlement tidak dipakai → rekonsiliasi memakai impor laporan/mutasi.
 */
class XenditGateway implements PaymentGatewayContract
{
    public const EXTERNAL_ID_PREFIX = 'PMB-';

    /** Kode kanal PMB → kode `payment_methods` Xendit. */
    private const CHANNELS = [
        'va_bca' => 'BCA', 'va_bni' => 'BNI', 'va_bri' => 'BRI', 'va_mandiri' => 'MANDIRI', 'va_permata' => 'PERMATA',
        'va_bsi' => 'BSI', 'va_cimb' => 'CIMB', 'va_bjb' => 'BJB', 'qris' => 'QRIS',
        'ovo' => 'OVO', 'dana' => 'DANA', 'shopeepay' => 'SHOPEEPAY', 'linkaja' => 'LINKAJA',
        'alfamart' => 'ALFAMART', 'indomaret' => 'INDOMARET',
    ];

    public function __construct(
        private readonly string $secretKey,
        private readonly string $webhookToken,
        private readonly string $baseUrl = 'https://api.xendit.co',
        private readonly int $timeout = 10,
    ) {}

    public function name(): string
    {
        return 'xendit';
    }

    public function supportedChannels(): array
    {
        return array_keys(self::CHANNELS);
    }

    public function createCharge(PaymentTransaction $transaction, string $channel, string $amount, int $expiryHours): ChargeResult
    {
        $method = self::CHANNELS[$channel] ?? throw new GatewayUnsupported("Kanal {$channel} tidak didukung Xendit.");

        $response = $this->send(fn (PendingRequest $http) => $http->post('/v2/invoices', [
            'external_id' => self::EXTERNAL_ID_PREFIX.$transaction->getKey(),
            'amount' => (int) round(Money::toCents($amount) / 100),
            'currency' => 'IDR',
            'description' => 'Pembayaran PMB '.$transaction->invoice->invoice_number,
            'invoice_duration' => $expiryHours * 3600,
            'payment_methods' => [$method],
            'should_send_email' => false,
        ]));
        $data = $response->json() ?? [];

        if (! $response->successful() || ! isset($data['id'])) {
            throw new GatewayUnavailable('Penyedia pembayaran menolak permintaan: '.($data['message'] ?? 'tidak diketahui').'.');
        }

        // Nomor VA hanya ada bila Xendit langsung membuatnya; selain itu dibuat di halaman checkout.
        $va = collect($data['available_banks'] ?? [])->firstWhere('bank_code', $method)['bank_account_number'] ?? null;

        return new ChargeResult(
            providerReference: (string) $data['id'],
            vaNumber: $va,
            redirectUrl: $data['invoice_url'] ?? null,
            expiresAt: $data['expiry_date'] ?? null,
            instructions: $this->instructions($channel, $method, $va),
            rawStatus: $data['status'] ?? null,
        );
    }

    public function getStatus(string $providerReference): GatewayStatus
    {
        $response = $this->send(fn (PendingRequest $http) => $http->get('/v2/invoices/'.rawurlencode($providerReference)));

        if ($response->notFound()) {
            return new GatewayStatus(GatewayStatus::PENDING, null, raw: 'not_found');
        }

        if (! $response->successful()) {
            throw new GatewayUnavailable;
        }

        $data = $response->json() ?? [];
        $raw = (string) ($data['status'] ?? 'PENDING');
        $status = match ($raw) {
            'PAID', 'SETTLED' => GatewayStatus::SUCCESS,
            'EXPIRED' => GatewayStatus::EXPIRED,
            default => GatewayStatus::PENDING,
        };
        $amount = $data['paid_amount'] ?? $data['amount'] ?? null;

        return new GatewayStatus($status, $amount !== null ? Money::fromCents(Money::toCents((string) $amount)) : null, (string) ($data['currency'] ?? 'IDR'), $data['paid_at'] ?? null, $raw);
    }

    public function cancel(string $providerReference): bool
    {
        try {
            return $this->send(fn (PendingRequest $http) => $http->post('/invoices/'.rawurlencode($providerReference).'/expire!'))->successful();
        } catch (GatewayUnavailable) {
            return false;
        }
    }

    public function refund(string $providerReference, string $amount, string $reason): bool
    {
        $referenceId = 'rf-'.$providerReference.'-'.substr(sha1($amount.$reason), 0, 8);
        $response = $this->send(fn (PendingRequest $http) => $http->withHeaders(['Idempotency-key' => $referenceId])->post('/refunds', [
            'invoice_id' => $providerReference,
            'reference_id' => $referenceId,
            'amount' => (int) round(Money::toCents($amount) / 100),
            'reason' => 'REQUESTED_BY_CUSTOMER',
            'metadata' => ['note' => mb_substr($reason, 0, 255)],
        ]));

        if ($response->clientError()) {
            // VA, QRIS & gerai ritel tidak dapat di-refund via API → pelaksanaan manual.
            throw new GatewayUnsupported('Refund via API tidak didukung untuk transaksi ini: '.($response->json('message') ?? 'ditolak penyedia').'.');
        }

        // PENDING = refund diterima Xendit dan sedang diproses (e-wallet); hasil akhir terlihat di dashboard Xendit.
        return in_array($response->json('status'), ['SUCCEEDED', 'PENDING'], true);
    }

    public function verifyWebhook(Request $request): ?WebhookEvent
    {
        $payload = $request->json()->all();
        $invoiceId = (string) ($payload['id'] ?? '');
        $token = (string) $request->header('x-callback-token', '');

        if ($invoiceId === '' || $token === '' || $this->webhookToken === '' || ! hash_equals($this->webhookToken, $token)) {
            return null;
        }

        $status = (string) ($payload['status'] ?? 'PENDING');
        // Pengiriman ulang callback yang sama menghasilkan id yang sama (05 §F.4 aturan 2).
        $eventId = hash('sha256', $invoiceId.'|'.$status.'|'.($payload['paid_at'] ?? $payload['updated'] ?? ''));

        return new WebhookEvent($eventId, $invoiceId, $status, $payload);
    }

    public function fetchSettlement(string $date): array
    {
        throw new GatewayUnsupported('Settlement Xendit tidak ditarik otomatis; gunakan impor laporan/mutasi.');
    }

    /**
     * @param  callable(PendingRequest): Response  $call
     */
    private function send(callable $call): Response
    {
        try {
            $response = $call(Http::baseUrl($this->baseUrl)->withBasicAuth($this->secretKey, '')->acceptJson()->asJson()->timeout($this->timeout));
        } catch (Throwable) {
            throw new GatewayUnavailable;
        }

        if ($response->serverError()) {
            throw new GatewayUnavailable;
        }

        return $response;
    }

    /** @return array<int, array{title: string, steps: array<int, string>}> */
    private function instructions(string $channel, string $method, ?string $va): array
    {
        $open = 'Buka halaman pembayaran Xendit melalui tautan yang tersedia.';

        if ($method === 'QRIS') {
            return [['title' => 'QRIS', 'steps' => [$open, 'Pindai kode QR dengan aplikasi bank/e-wallet yang mendukung QRIS.', 'Periksa nominal lalu konfirmasi pembayaran.']]];
        }

        if (str_starts_with($channel, 'va_')) {
            if ($va === null) {
                return [['title' => "Virtual Account {$method}", 'steps' => [$open, "Salin nomor Virtual Account {$method} yang ditampilkan.", "Bayar melalui ATM, mobile banking, atau internet banking {$method}."]]];
            }

            return [
                ['title' => "ATM {$method}", 'steps' => ['Pilih menu Transfer / Pembayaran Virtual Account.', "Masukkan nomor Virtual Account {$va}.", 'Periksa nama & nominal, lalu konfirmasi.']],
                ['title' => "Mobile Banking {$method}", 'steps' => ['Masuk ke aplikasi mobile banking.', "Pilih Virtual Account dan masukkan {$va}.", 'Konfirmasi pembayaran.']],
            ];
        }

        if (in_array($method, ['ALFAMART', 'INDOMARET'], true)) {
            return [['title' => $method, 'steps' => [$open, 'Catat kode pembayaran yang ditampilkan.', "Bayar di kasir {$method} dengan menyebutkan kode pembayaran."]]];
        }

        return [['title' => $method, 'steps' => [$open, "Pilih {$method} lalu ikuti instruksi di aplikasi {$method}.", 'Konfirmasi pembayaran.']]];
    }
}
