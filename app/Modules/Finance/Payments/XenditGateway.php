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
 * Adapter Xendit Invoice API untuk KEU. Satu transaksi = satu invoice Xendit
 * yang dibatasi ke kanal pilihan (`payment_methods`); mahasiswa membayar di
 * halaman checkout Xendit (`invoice_url`). Callback diverifikasi dengan header
 * `x-callback-token` lalu status selalu dicek ulang lewat GET /v2/invoices/{id}
 * (BR-KEU-41). Aktif hanya bila XENDIT_SECRET_KEY diisi.
 *
 * `provider_reference` = ID invoice Xendit. `external_id` = "KEU-" + ID
 * transaksi (ULID, unik lintas tenant) — prefiks dipakai
 * App\Http\Controllers\XenditWebhookController untuk mengarahkan callback
 * dari akun Xendit yang sama dengan PMB.
 */
class XenditGateway implements PaymentGateway
{
    public const EXTERNAL_ID_PREFIX = 'KEU-';

    private const BANKS = ['BCA', 'BNI', 'BRI', 'MANDIRI', 'PERMATA', 'BSI', 'CIMB', 'BJB'];

    private const EWALLETS = ['OVO', 'DANA', 'SHOPEEPAY', 'LINKAJA'];

    private const RETAIL = ['ALFAMART', 'INDOMARET'];

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

    public function supportedMethods(): array
    {
        return [
            ...array_map(fn ($b) => 'va_'.strtolower($b), self::BANKS),
            'qris',
            ...array_map(fn ($e) => 'ewallet_'.strtolower($e), self::EWALLETS),
            ...array_map(fn ($r) => 'retail_'.strtolower($r), self::RETAIL),
        ];
    }

    public function createCharge(PaymentTransaction $transaction, PaymentChannel $channel): ChargeResult
    {
        $code = strtoupper((string) $channel->bank_code);
        $method = match ($channel->method) {
            'va' => in_array($code, self::BANKS, true) ? $code : null,
            'qris' => 'QRIS',
            'ewallet' => in_array($code, self::EWALLETS, true) ? $code : null,
            'retail' => in_array($code, self::RETAIL, true) ? $code : null,
            default => null,
        } ?? throw new GatewayUnavailable("Metode {$channel->method} {$code} tidak didukung Xendit.");

        $response = $this->send(fn (PendingRequest $http) => $http->post('/v2/invoices', [
            'external_id' => self::EXTERNAL_ID_PREFIX.$transaction->id,
            'amount' => (int) round(Money::toCents($transaction->amount) / 100),
            'currency' => 'IDR',
            'description' => 'Pembayaran '.$transaction->transaction_number,
            'invoice_duration' => max(1, $channel->expiry_minutes) * 60,
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
            instructions: $this->instructions($channel->method, $method, $va),
        );
    }

    public function getStatus(string $providerReference): GatewayStatus
    {
        $response = $this->send(fn (PendingRequest $http) => $http->get('/v2/invoices/'.rawurlencode($providerReference)));

        if ($response->notFound()) {
            return new GatewayStatus(GatewayStatus::PENDING, null);
        }

        if (! $response->successful()) {
            throw new GatewayUnavailable;
        }

        $data = $response->json() ?? [];
        $status = match ((string) ($data['status'] ?? 'PENDING')) {
            'PAID', 'SETTLED' => GatewayStatus::SUCCESS,
            'EXPIRED' => GatewayStatus::EXPIRED,
            default => GatewayStatus::PENDING,
        };
        $amount = $data['paid_amount'] ?? $data['amount'] ?? null;

        return new GatewayStatus($status, $amount !== null ? Money::normalize((string) $amount) : null, $data['paid_at'] ?? null);
    }

    public function cancel(string $providerReference): bool
    {
        try {
            return $this->send(fn (PendingRequest $http) => $http->post('/invoices/'.rawurlencode($providerReference).'/expire!'))->successful();
        } catch (GatewayUnavailable) {
            return false;
        }
    }

    public function verifyWebhook(Request $request): ?WebhookEvent
    {
        $payload = $request->json()->all();
        $invoiceId = (string) ($payload['id'] ?? '');
        $token = (string) $request->header('x-callback-token', '');

        if ($invoiceId === '' || $token === '' || $this->webhookToken === '' || ! hash_equals($this->webhookToken, $token)) {
            return null;
        }

        // Pengiriman ulang callback yang sama menghasilkan id yang sama (dedupe BR-KEU-44).
        $eventId = hash('sha256', $invoiceId.'|'.($payload['status'] ?? '').'|'.($payload['paid_at'] ?? $payload['updated'] ?? ''));

        return new WebhookEvent($eventId, $invoiceId, $payload);
    }

    /** @param  callable(PendingRequest): Response  $call */
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
    private function instructions(string $channelMethod, string $method, ?string $va): array
    {
        $open = 'Buka halaman pembayaran Xendit melalui tautan "Lanjutkan pembayaran di halaman penyedia".';

        return match (true) {
            $method === 'QRIS' => [['title' => 'QRIS', 'steps' => [$open, 'Pindai kode QR dengan aplikasi bank/e-wallet yang mendukung QRIS.', 'Periksa nominal lalu konfirmasi pembayaran.']]],
            $channelMethod === 'va' && $va !== null => [
                ['title' => "ATM {$method}", 'steps' => ['Pilih menu Transfer / Pembayaran Virtual Account.', "Masukkan nomor Virtual Account {$va}.", 'Periksa nama & nominal, lalu konfirmasi.']],
                ['title' => "Mobile Banking {$method}", 'steps' => ['Masuk ke aplikasi mobile banking.', "Pilih Virtual Account dan masukkan {$va}.", 'Konfirmasi pembayaran.']],
            ],
            $channelMethod === 'va' => [['title' => "Virtual Account {$method}", 'steps' => [$open, "Salin nomor Virtual Account {$method} yang ditampilkan.", "Bayar melalui ATM, mobile banking, atau internet banking {$method}."]]],
            $channelMethod === 'retail' => [['title' => $method, 'steps' => [$open, 'Catat kode pembayaran yang ditampilkan.', "Bayar di kasir {$method} dengan menyebutkan kode pembayaran."]]],
            default => [['title' => $method, 'steps' => [$open, "Pilih {$method} lalu ikuti instruksi di aplikasi {$method}.", 'Konfirmasi pembayaran.']]],
        };
    }
}
