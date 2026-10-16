<?php

namespace Modules\Finance\Controllers\Admin;

use App\Support\Http\ApiResponse;
use App\Support\Http\Exceptions\ConflictException;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\AuditLog\Enums\AuditAction;
use Modules\AuditLog\Services\AuditLogService;
use Modules\Finance\Controllers\KeuController;
use Modules\Finance\Enums\ChannelCategory;
use Modules\Finance\Enums\ChannelStatus;
use Modules\Finance\Models\PaymentChannel;
use Modules\Finance\Payments\GatewayRegistry;
use Modules\Finance\Services\ChannelService;
use Modules\Finance\Support\KeuSettings;
use Modules\SystemSetting\Enums\SettingValueType;

/** Pengaturan KEU (`keu.*`) & registri kanal pembayaran dengan kuota paket (§9.5, §14.3). */
class SettingsController extends KeuController
{
    public function __construct(private readonly KeuSettings $settings, private readonly ChannelService $channels, private readonly AuditLogService $audit) {}

    public function show(): JsonResponse
    {
        return ApiResponse::success($this->settings->all());
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate(['settings' => ['required', 'array']]);
        $changed = [];

        foreach ($data['settings'] as $key => $value) {
            if (! isset(KeuSettings::DEFINITIONS[$key])) {
                throw new ConflictException("Pengaturan {$key} tidak dikenal.");
            }

            if ($key === 'keu.payment_channels.limit_override') {
                continue; // hanya Super Admin (platform) — lihat updateQuotaOverride
            }

            $type = KeuSettings::DEFINITIONS[$key][0];

            if (($type === SettingValueType::Integer && ! is_numeric($value)) || ($type === SettingValueType::Json && ! is_array($value))) {
                throw new ConflictException("Nilai {$key} tidak valid.");
            }

            $changed[$key] = ['from' => $this->settings->get($key), 'to' => $value];
            $this->settings->set($key, $value, $this->user($request)->id);
        }

        if ($changed !== []) {
            $this->audit->log('finance', 'Perubahan pengaturan KEU', null, ['changes' => $changed]);
        }

        return ApiResponse::success($this->settings->all(), 'Pengaturan disimpan.');
    }

    public function channels(GatewayRegistry $gateways): JsonResponse
    {
        return ApiResponse::success([
            'quota' => $this->channels->quota(),
            'configured_providers' => $gateways->names(),
            'channels' => PaymentChannel::query()->orderBy('sort_order')->get()->map(fn (PaymentChannel $c) => [
                ...$c->only(['id', 'code', 'name', 'provider', 'method', 'bank_code', 'fee_bearer', 'expiry_minutes', 'instructions', 'counts_toward_quota', 'environment', 'sort_order']),
                'category' => $c->category->value, 'category_label' => $c->category->label(), 'status' => $c->status->value, 'status_label' => $c->status->label(),
                'fee_amount' => (string) $c->fee_amount, 'counts_in_quota' => $this->channels->counts($c),
                'connected' => $c->category === ChannelCategory::Offline || $gateways->has($c->provider),
            ]),
        ]);
    }

    public function storeChannel(Request $request): JsonResponse
    {
        $channel = PaymentChannel::query()->create([...$this->validateChannel($request), 'status' => ChannelStatus::Inactive, 'created_by' => $this->user($request)->id]);

        return ApiResponse::success($channel, 'Kanal dibuat (nonaktif). Aktifkan bila kontrak & kredensial penyedia sudah siap.', status: 201);
    }

    public function updateChannel(Request $request, PaymentChannel $paymentChannel): JsonResponse
    {
        $paymentChannel->update($this->validateChannel($request, $paymentChannel));
        $this->audit->recordAction($paymentChannel, AuditAction::Updated, ['code' => $paymentChannel->code]);

        return ApiResponse::success($paymentChannel->fresh(), 'Kanal diperbarui.');
    }

    public function setChannelStatus(Request $request, PaymentChannel $paymentChannel): JsonResponse
    {
        $data = $request->validate(['status' => ['required', Rule::enum(ChannelStatus::class)]]);
        $status = ChannelStatus::from($data['status']);
        $channel = $status === ChannelStatus::Active ? $this->channels->activate($paymentChannel) : tap($paymentChannel)->forceFill(['status' => $status])->save();
        $this->audit->recordAction($paymentChannel, AuditAction::Updated, ['status' => $status->value]);

        return ApiResponse::success(['quota' => $this->channels->quota(), 'status' => $paymentChannel->fresh()->status->value], 'Status kanal diperbarui.');
    }

    /** Penimpaan kuota kanal oleh Super Admin platform (B-KEU-03 / D-KEU-04). */
    public function updateQuotaOverride(Request $request): JsonResponse
    {
        abort_unless($this->user($request)->hasRole('super_admin'), 403, 'Hanya Super Admin platform yang dapat menimpa kuota kanal paket.');
        $data = $request->validate(['limit' => ['required', 'integer', 'between:0,20']]);
        $this->settings->set('keu.payment_channels.limit_override', $data['limit'], $this->user($request)->id);
        $this->audit->log('finance', 'Penimpaan kuota kanal', null, ['limit' => $data['limit'], 'university_id' => app(TenantContext::class)->universityId()]);

        return ApiResponse::success($this->channels->quota(), 'Kuota kanal diperbarui.');
    }

    /** @return array<string, mixed> */
    private function validateChannel(Request $request, ?PaymentChannel $channel = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:40', 'alpha_dash', Rule::unique('payment_channels', 'code')->where('university_id', app(TenantContext::class)->universityId())->ignore($channel?->id)],
            'name' => ['required', 'string', 'max:150'],
            'category' => ['required', Rule::enum(ChannelCategory::class)],
            'provider' => ['required', 'string', 'max:30'],
            'method' => ['required', Rule::in(['va', 'qris', 'ewallet', 'retail', 'biller', 'cash', 'manual_transfer'])],
            'bank_code' => ['nullable', 'string', 'max:20'],
            'fee_amount' => ['required', 'numeric', 'min:0'],
            'fee_bearer' => ['required', Rule::in(['campus', 'student'])],
            'expiry_minutes' => ['required', 'integer', 'between:15,10080'],
            'instructions' => ['nullable', 'string', 'max:4000'],
            'counts_toward_quota' => ['boolean'],
            'environment' => ['required', Rule::in(['production', 'sandbox'])],
            'sort_order' => ['nullable', 'integer', 'between:0,999'],
        ]);
    }
}
