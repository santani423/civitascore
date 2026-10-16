<?php

namespace Modules\Finance\Services;

use App\Support\Http\Exceptions\ConflictException;
use App\Support\Tenancy\TenantContext;
use Modules\Finance\Enums\ChannelCategory;
use Modules\Finance\Enums\ChannelStatus;
use Modules\Finance\Models\PaymentChannel;
use Modules\Finance\Payments\GatewayRegistry;
use Modules\Finance\Support\KeuSettings;
use Modules\Tenancy\Models\UniversitySubscription;

/**
 * Registri kanal & kuota paket (rancangan KEU §14.3). Kanal online
 * produksi yang dihitung kuota (bank/e-commerce) dibatasi
 * `keu.payment_channels`: Growth 3, Business 3, Professional 4, Premium 5,
 * Enterprise 6 (acuan). Kanal offline (tunai, transfer manual) dan sandbox
 * tidak dihitung.
 */
class ChannelService
{
    public function __construct(
        private readonly TenantContext $tenant,
        private readonly KeuSettings $settings,
        private readonly GatewayRegistry $gateways,
    ) {}

    /** @return array{limit: int, used: int, plan: ?string, source: string} */
    public function quota(): array
    {
        $override = $this->settings->int('keu.payment_channels.limit_override');
        $subscription = UniversitySubscription::query()->where('university_id', $this->tenant->universityId())
            ->whereIn('status', ['active', 'trial'])->with('plan')->latest('current_period_starts_at')->first();
        $plan = $subscription?->plan;
        $planLimit = $plan?->limits['keu_payment_channels'] ?? null;
        $configured = config('keu.payment_channel_limits');

        [$limit, $source] = match (true) {
            $override > 0 => [$override, 'override'],
            is_numeric($planLimit) => [(int) $planLimit, 'plan_limits'],
            $plan !== null && isset($configured[$plan->code]) => [(int) $configured[$plan->code], 'plan_default'],
            default => [(int) config('keu.default_payment_channel_limit', 3), 'default'],
        };

        return ['limit' => $limit, 'used' => $this->countedActive(), 'plan' => $plan?->code, 'source' => $source];
    }

    public function activate(PaymentChannel $channel): PaymentChannel
    {
        if ($this->counts($channel) && $channel->status !== ChannelStatus::Active) {
            $quota = $this->quota();

            if ($quota['used'] >= $quota['limit']) {
                throw new ConflictException("Kuota kanal pembayaran online paket Anda sudah terpakai ({$quota['used']} dari {$quota['limit']}). Nonaktifkan kanal lain atau tingkatkan paket.", 'CHANNEL_QUOTA_EXCEEDED');
            }
        }

        $channel->forceFill(['status' => ChannelStatus::Active])->save();

        return $channel;
    }

    /** Kanal online yang dapat dipakai bertransaksi saat ini (aktif & adapter tersedia). */
    public function isUsable(PaymentChannel $channel): bool
    {
        return $channel->status === ChannelStatus::Active
            && ($channel->category === ChannelCategory::Offline || $this->gateways->has($channel->provider));
    }

    public function counts(PaymentChannel $channel): bool
    {
        return $channel->category !== ChannelCategory::Offline && $channel->counts_toward_quota && $channel->environment === 'production';
    }

    private function countedActive(): int
    {
        return PaymentChannel::query()->whereIn('status', [ChannelStatus::Active, ChannelStatus::Maintenance])->where('category', '!=', ChannelCategory::Offline)
            ->where('counts_toward_quota', true)->where('environment', 'production')->count();
    }
}
