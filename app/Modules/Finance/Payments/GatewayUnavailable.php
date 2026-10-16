<?php

namespace Modules\Finance\Payments;

use App\Support\Http\Exceptions\ConflictException;

final class GatewayUnavailable extends ConflictException
{
    public function __construct(string $message = 'Layanan penyedia pembayaran sedang tidak dapat dihubungi. Silakan coba lagi atau gunakan kanal lain.')
    {
        parent::__construct($message, 'GATEWAY_UNAVAILABLE');
    }
}
