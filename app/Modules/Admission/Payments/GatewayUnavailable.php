<?php

namespace Modules\Admission\Payments;

use Modules\Admission\Exceptions\DomainRuleException;

class GatewayUnavailable extends DomainRuleException
{
    public function __construct(string $message = 'Layanan pembayaran sedang tidak tersedia. Coba kanal lain atau beberapa saat lagi.')
    {
        parent::__construct($message, 'PAYMENT_PROVIDER_UNAVAILABLE', 503);
    }
}
