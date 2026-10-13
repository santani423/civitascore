<?php

namespace Modules\Admission\Outbox\Handlers;

use Illuminate\Support\Facades\Notification;
use Modules\Admission\Models\ApplicantAccount;
use Modules\Admission\Models\OutboxEvent;
use Modules\Admission\Notifications\PmbMessageNotification;
use Modules\Admission\Outbox\OutboxHandler;
use Modules\Admission\Support\PmbNotifier;
use RuntimeException;

/**
 * Notifikasi pendaftar. Kanal yang gagal disimpan kembali ke payload sehingga
 * retry hanya mengirim ulang kanal tersebut (tidak menggandakan yang sukses).
 */
class NotifyApplicantHandler implements OutboxHandler
{
    public function __construct(private readonly PmbNotifier $notifier) {}

    public function handle(OutboxEvent $event): void
    {
        $payload = (array) $event->payload;
        $account = ApplicantAccount::query()->find($payload['account_id'] ?? null);

        if ($account === null) {
            return;
        }

        if (! empty($payload['override_email'])) {
            // Verifikasi ganti email: dikirim ke alamat BARU, bukan alamat akun.
            $content = $this->notifier->render((string) $account->university_id, (string) $payload['event_key'], 'mail', [...(array) ($payload['placeholders'] ?? []), 'applicant_name' => $account->name]);
            Notification::route('mail', (string) $payload['override_email'])->notifyNow(new PmbMessageNotification((string) $payload['event_key'], $content['subject'], $content['body'], ['mail']));

            return;
        }

        $channels = $payload['channels'] ?? null;

        if (! empty($payload['force_sms']) && $account->phone) {
            // OTP: kanal SMS/WA dipilih tenant sebagai kanal verifikasi (FR-ACC-02).
            $content = $this->notifier->render((string) $account->university_id, (string) $payload['event_key'], 'sms', [...(array) ($payload['placeholders'] ?? []), 'applicant_name' => $account->name]);
            $account->notifyNow(new PmbMessageNotification((string) $payload['event_key'], $content['subject'], $content['body'], ['sms'], [], 'Kode PMB: '.($payload['placeholders']['otp_code'] ?? '').' (berlaku 5 menit). Jangan bagikan kode ini.'));

            return;
        }

        $failed = $this->notifier->sendToApplicant($account, (string) $payload['event_key'], (array) ($payload['placeholders'] ?? []), (array) ($payload['data'] ?? []), $channels);

        if ($failed !== []) {
            $event->forceFill(['payload' => [...$payload, 'channels' => $failed]])->save();

            throw new RuntimeException('Kanal gagal: '.implode(',', $failed));
        }
    }
}
