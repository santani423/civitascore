<?php

namespace Modules\Admission\Contracts;

/** Penyedia SMS/WhatsApp (11 §B.4). Implementasi dipilih via config pmb.sms.driver. */
interface SmsGateway
{
    /**
     * @throws \RuntimeException bila pengiriman gagal (memicu retry/log gagal)
     */
    public function send(string $phoneE164, string $message): void;
}
