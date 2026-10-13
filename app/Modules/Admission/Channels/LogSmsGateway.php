<?php

namespace Modules\Admission\Channels;

use Illuminate\Support\Facades\Log;
use Modules\Admission\Contracts\SmsGateway;

/**
 * Driver `log`: TIDAK mengirim SMS — hanya mencatat (nomor disamarkan).
 * Dipakai bila tenant belum mengontrak penyedia SMS/WA (DEP-11).
 */
class LogSmsGateway implements SmsGateway
{
    public function send(string $phoneE164, string $message): void
    {
        Log::info('[pmb-sms:log-driver] SMS tidak dikirim (driver log).', [
            'to' => substr($phoneE164, 0, 4).'****'.substr($phoneE164, -3),
            'length' => mb_strlen($message),
        ]);
    }
}
