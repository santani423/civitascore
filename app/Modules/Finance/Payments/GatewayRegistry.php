<?php

namespace Modules\Finance\Payments;

/** Daftar adapter penyedia yang benar-benar terkonfigurasi pada instalasi ini. */
class GatewayRegistry
{
    /** @var array<string, PaymentGateway> */
    private array $gateways = [];

    public function register(PaymentGateway $gateway): void
    {
        $this->gateways[$gateway->name()] = $gateway;
    }

    public function has(string $provider): bool
    {
        return isset($this->gateways[$provider]);
    }

    public function get(string $provider): PaymentGateway
    {
        return $this->gateways[$provider] ?? throw new GatewayUnavailable("Penyedia pembayaran '{$provider}' belum dikonfigurasi pada sistem.");
    }

    /** @return array<int, string> */
    public function names(): array
    {
        return array_keys($this->gateways);
    }
}
