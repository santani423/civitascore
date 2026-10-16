<?php

namespace Modules\Admission\Payments;

use Modules\Admission\Support\PmbSettings;

/**
 * Menentukan adapter gateway aktif (Xendit, config services.xendit) dan daftar
 * kanal tenant (`pmb.payment.channels`, BR-17). Kanal `manual_transfer` &
 * `cashier` tidak memerlukan gateway.
 */
class PaymentGatewayManager
{
    public const OFFLINE_PROVIDERS = ['manual', 'cashier'];

    /** @var array<string, PaymentGatewayContract> */
    private array $gateways = [];

    public function __construct(private readonly PmbSettings $settings) {}

    public function register(PaymentGatewayContract $gateway): void
    {
        $this->gateways[$gateway->name()] = $gateway;
    }

    public function gateway(string $provider): PaymentGatewayContract
    {
        return $this->gateways[$provider] ?? throw new GatewayUnavailable("Penyedia pembayaran '{$provider}' belum dikonfigurasi.");
    }

    public function has(string $provider): bool
    {
        return isset($this->gateways[$provider]);
    }

    /**
     * Kanal aktif tenant yang benar-benar dapat dipakai (gateway terkonfigurasi).
     *
     * @return array<int, array<string, mixed>>
     */
    public function activeChannels(): array
    {
        $channels = (array) $this->settings->get('pmb.payment.channels');

        return collect($channels)
            ->filter(fn ($c) => ($c['is_active'] ?? true) && (in_array($c['provider'] ?? '', self::OFFLINE_PROVIDERS, true) || $this->has($c['provider'] ?? '')))
            ->sortBy('sort')
            ->values()
            ->all();
    }

    /** @return array<string, mixed>|null */
    public function channel(string $code): ?array
    {
        return collect($this->activeChannels())->firstWhere('code', $code);
    }
}
