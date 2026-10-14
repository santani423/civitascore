<?php

namespace Modules\Admission\Channels;

use Illuminate\Support\Facades\Http;
use Modules\Admission\Contracts\SmsGateway;
use RuntimeException;

/**
 * Driver `http`: POST JSON {to, message} ke endpoint agregator SMS/WA
 * dengan Bearer token (konfigurasi pmb.sms.*). Format spesifik penyedia
 * dapat ditambahkan sebagai adapter baru tanpa mengubah domain PMB.
 */
class HttpSmsGateway implements SmsGateway
{
    public function send(string $phoneE164, string $message): void
    {
        $endpoint = config('pmb.sms.http_endpoint');

        if (! $endpoint) {
            throw new RuntimeException('PMB_SMS_HTTP_ENDPOINT belum dikonfigurasi.');
        }

        $response = Http::timeout(10)
            ->withToken((string) config('pmb.sms.http_token'))
            ->acceptJson()
            ->post($endpoint, ['to' => $phoneE164, 'message' => $message]);

        if ($response->failed()) {
            throw new RuntimeException('Penyedia SMS menolak permintaan (HTTP '.$response->status().').');
        }
    }
}
