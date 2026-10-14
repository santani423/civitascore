<?php

namespace Modules\Admission\Support;

use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Admission\Models\ApplicantAccount;
use Modules\Admission\Notifications\PmbMessageNotification;
use Modules\Notification\Models\NotificationTemplate;
use Modules\Tenancy\Models\University;
use Throwable;

/**
 * Merender & mengirim notifikasi PMB (13 §A): template tenant
 * (`notification_templates`) dengan fallback NotificationCatalog,
 * placeholder allowlist, kanal per event, jam tenang SMS, dan pengiriman
 * per kanal sehingga retry outbox tidak menggandakan kanal yang sudah sukses.
 *
 * Dipanggil oleh handler outbox, BUKAN langsung dari transaksi domain —
 * gunakan OutboxRecorder::notifyApplicant() dari service.
 */
class PmbNotifier
{
    public function __construct(private readonly PmbSettings $settings) {}

    /**
     * @param  array<string, string|int|float|null>  $placeholders
     * @param  array<string, mixed>  $data
     * @param  array<int, string>|null  $channels
     * @return array<int, string> kanal yang gagal dikirim
     */
    public function sendToApplicant(ApplicantAccount $account, string $eventKey, array $placeholders = [], array $data = [], ?array $channels = null): array
    {
        if ($account->anonymized_at !== null) {
            return [];
        }

        $university = University::query()->find($account->university_id);
        $placeholders = [
            'applicant_name' => $account->name,
            'university_name' => $university?->name ?? '',
            'portal_url' => rtrim((string) config('pmb.frontend_url'), '/').'/pmb/portal',
            'contact' => (string) (($this->settings->get('pmb.contact')['email'] ?? '') ?: ''),
            ...$placeholders,
        ];
        $data['link'] ??= $placeholders['portal_url'];

        $channels ??= NotificationCatalog::EVENTS[$eventKey][0] ?? ['database'];

        if (! $this->settings->bool('pmb.sms.enabled') || ! $account->phone) {
            $channels = array_values(array_diff($channels, ['sms']));
        }

        return $this->deliver($account, (string) $account->university_id, $eventKey, $placeholders, $data, $channels);
    }

    /**
     * Notifikasi staf (pengesah, admin, keuangan) — ke semua pengguna tenant
     * yang memegang salah satu role dengan permission tersebut.
     *
     * @param  array<string, string|int|float|null>  $placeholders
     * @param  array<string, mixed>  $data
     * @return array<int, string>
     */
    public function sendToPermissionHolders(string $universityId, string $permission, string $eventKey, array $placeholders = [], array $data = []): array
    {
        $userIds = DB::table('user_roles')
            ->join('role_permissions', 'role_permissions.role_id', '=', 'user_roles.role_id')
            ->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
            ->join('user_universities', function ($join) use ($universityId): void {
                $join->on('user_universities.user_id', '=', 'user_roles.user_id')->where('user_universities.university_id', $universityId);
            })
            ->where('permissions.slug', $permission)
            ->where(fn ($q) => $q->whereNull('user_roles.university_id')->orWhere('user_roles.university_id', $universityId))
            ->distinct()
            ->pluck('user_roles.user_id');

        $university = University::query()->find($universityId);
        $failed = [];

        foreach (User::query()->whereIn('id', $userIds)->get() as $user) {
            $channels = array_values(array_diff(NotificationCatalog::EVENTS[$eventKey][0] ?? ['database'], ['sms']));
            $failed = [...$failed, ...$this->deliver($user, $universityId, $eventKey, [
                'applicant_name' => $user->name,
                'university_name' => $university?->name ?? '',
                'portal_url' => rtrim((string) config('pmb.frontend_url'), '/').'/admin/pmb',
                ...$placeholders,
            ], $data, $channels)];
        }

        return array_values(array_unique($failed));
    }

    /**
     * @param  array<string, string|int|float|null>  $placeholders
     * @return array{subject: string, body: string}
     */
    public function render(string $universityId, string $eventKey, string $channel, array $placeholders): array
    {
        [, , $defaultSubject, $defaultBody] = NotificationCatalog::EVENTS[$eventKey] ?? [[], '', $eventKey, ''];

        $template = NotificationTemplate::query()->withoutGlobalScopes()
            ->where('university_id', $universityId)
            ->where('event_key', $eventKey)
            ->where('channel', $channel === 'sms' ? 'sms' : ($channel === 'mail' ? 'mail' : 'database'))
            ->where('is_active', true)
            ->first();

        $allowed = array_flip(NotificationCatalog::allowedPlaceholders($eventKey));
        $replacements = [];

        foreach ($placeholders as $key => $value) {
            if (isset($allowed[$key])) {
                $replacements['{{'.$key.'}}'] = (string) $value;
            }
        }

        $subject = strtr($template?->subject ?: $defaultSubject, $replacements);
        $body = strtr($template?->body_template ?: $defaultBody, $replacements);

        // Placeholder yang tidak dikenal/ tidak terisi tidak boleh bocor ke penerima.
        $clean = fn (string $text): string => trim((string) preg_replace('/\{\{\s*[a-z_]+\s*\}\}/', '', $text));

        return ['subject' => $clean($subject), 'body' => $clean($body)];
    }

    /**
     * @param  array<string, string|int|float|null>  $placeholders
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $channels
     * @return array<int, string>
     */
    private function deliver(object $notifiable, string $universityId, string $eventKey, array $placeholders, array $data, array $channels): array
    {
        $failed = [];
        $priority = NotificationCatalog::EVENTS[$eventKey][1] ?? NotificationCatalog::PRIORITY_NORMAL;

        foreach ($channels as $channel) {
            if ($channel === 'sms' && $priority !== NotificationCatalog::PRIORITY_HIGH && $this->inQuietHours()) {
                // Jam tenang (13 §A.4): SMS prioritas normal tidak dikirim sekarang;
                // dikembalikan sebagai "gagal" agar outbox menjadwalkan ulang.
                $failed[] = $channel;

                continue;
            }

            $content = $this->render($universityId, $eventKey, $channel, $placeholders);

            try {
                $notifiable->notifyNow(new PmbMessageNotification($eventKey, $content['subject'], $content['body'], [$channel], $data));
            } catch (Throwable $e) {
                Log::warning('[pmb-notify] pengiriman gagal', ['event' => $eventKey, 'channel' => $channel, 'error' => $e->getMessage()]);
                $failed[] = $channel;
            }
        }

        return $failed;
    }

    public function inQuietHours(?CarbonImmutable $now = null): bool
    {
        $window = $this->settings->get('pmb.quiet_hours') ?: ['start' => '21:00', 'end' => '06:00'];
        $timezone = University::query()->whereKey(app(TenantContext::class)->universityId())->value('timezone') ?: config('app.timezone');
        $now = ($now ?? CarbonImmutable::now())->setTimezone($timezone);
        $time = $now->format('H:i');
        [$start, $end] = [$window['start'] ?? '21:00', $window['end'] ?? '06:00'];

        return $start > $end ? ($time >= $start || $time < $end) : ($time >= $start && $time < $end);
    }
}
