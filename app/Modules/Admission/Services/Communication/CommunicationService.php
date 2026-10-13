<?php

namespace Modules\Admission\Services\Communication;

use App\Models\User;
use App\Support\Http\Exceptions\ConflictException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Admission\Enums\BroadcastStatus;
use Modules\Admission\Enums\ChatThreadStatus;
use Modules\Admission\Enums\ConsentType;
use Modules\Admission\Models\ApplicantAccount;
use Modules\Admission\Models\Application;
use Modules\Admission\Models\Broadcast;
use Modules\Admission\Models\ChatMessage;
use Modules\Admission\Models\ChatThread;
use Modules\Admission\Outbox\OutboxRecorder;
use Modules\Admission\Support\PmbFileStorage;
use Modules\Admission\Support\PmbNotifier;

/**
 * Quick chat pendaftar ↔ panitia (FR-COM-01) dan broadcast email/SMS ke
 * segmen pendaftar (FR-COM-02), dengan aturan keamanan konten 13 §A.5.
 */
class CommunicationService
{
    public const SECURITY_NOTICE = 'Panitia PMB TIDAK PERNAH meminta kata sandi atau kode OTP Anda. Jangan bagikan kepada siapa pun.';

    public function __construct(
        private readonly PmbFileStorage $storage,
        private readonly OutboxRecorder $outbox,
        private readonly PmbNotifier $notifier,
    ) {}

    public function openThread(ApplicantAccount $account, string $subject, string $message, ?string $applicationId, ?UploadedFile $attachment): ChatThread
    {
        if ($applicationId !== null && ! $account->applications()->whereKey($applicationId)->exists()) {
            throw ValidationException::withMessages(['application_id' => 'Pendaftaran tidak ditemukan.']);
        }

        return DB::transaction(function () use ($account, $subject, $message, $applicationId, $attachment): ChatThread {
            $thread = ChatThread::query()->create([
                'applicant_account_id' => $account->getKey(),
                'application_id' => $applicationId,
                'subject' => mb_substr($subject, 0, 200),
                'status' => ChatThreadStatus::Open,
                'last_message_at' => now(),
            ]);

            ChatMessage::query()->create(['thread_id' => $thread->getKey(), 'sender_type' => 'system', 'body' => self::SECURITY_NOTICE]);
            $this->postMessage($thread, 'applicant', (string) $account->getKey(), $message, $attachment);

            return $thread->refresh();
        });
    }

    public function postMessage(ChatThread $thread, string $senderType, string $senderId, string $body, ?UploadedFile $attachment): ChatMessage
    {
        if ($thread->status === ChatThreadStatus::Closed && $senderType === 'applicant') {
            $thread->forceFill(['status' => ChatThreadStatus::Open])->save();
        }

        $fileId = null;

        if ($attachment !== null) {
            $fileId = $this->storage->store($attachment, ['image/jpeg', 'image/png', 'application/pdf'], 2048, ['chat', (string) $thread->getKey()], uploadedByUserId: $senderType === 'user' ? $senderId : null)->getKey();
        }

        $message = ChatMessage::query()->create([
            'thread_id' => $thread->getKey(),
            'sender_type' => $senderType,
            'sender_id' => $senderId,
            'body' => mb_substr(strip_tags($body), 0, 5000),
            'attachment_file_id' => $fileId,
        ]);

        $thread->forceFill([
            'last_message_at' => now(),
            'status' => $senderType === 'user' ? ChatThreadStatus::PendingApplicant : ChatThreadStatus::Open,
        ])->save();

        if ($senderType === 'user') {
            $this->outbox->notifyApplicant($thread->account, 'pmb.chat.reply', [], ['thread_id' => $thread->getKey()], $message);
        }

        return $message;
    }

    public function reply(ChatThread $thread, User $user, string $body, ?UploadedFile $attachment): ChatMessage
    {
        if ($thread->assigned_user_id === null) {
            $thread->forceFill(['assigned_user_id' => $user->getKey()])->save();
        }

        return $this->postMessage($thread, 'user', (string) $user->getKey(), $body, $attachment);
    }

    public function markRead(ChatThread $thread, string $readerType): void
    {
        ChatMessage::query()->where('thread_id', $thread->getKey())->whereNull('read_at')
            ->where('sender_type', $readerType === 'applicant' ? 'user' : 'applicant')->update(['read_at' => now()]);
    }

    /** @param  array<string, mixed>  $data */
    public function updateThread(ChatThread $thread, array $data): ChatThread
    {
        if (array_key_exists('assigned_user_id', $data)) {
            $thread->assigned_user_id = $data['assigned_user_id'];
        }

        if (isset($data['status'])) {
            $thread->status = ChatThreadStatus::from($data['status']);
        }

        $thread->save();

        return $thread->refresh();
    }

    // ------------------------------------------------------------------ Broadcast

    /** @param  array<string, mixed>  $data */
    public function saveBroadcast(?Broadcast $broadcast, array $data, User $user): Broadcast
    {
        if ($broadcast !== null && $broadcast->status !== BroadcastStatus::Draft) {
            throw new ConflictException('Broadcast yang sudah dijadwalkan tidak dapat diubah.', 'INVALID_TRANSITION');
        }

        if (! in_array($data['channel'] ?? $broadcast?->channel, ['email', 'sms'], true)) {
            throw ValidationException::withMessages(['channel' => 'Kanal harus email atau sms.']);
        }

        $body = strip_tags((string) ($data['content_body'] ?? $broadcast?->content_body));

        // 13 §A.5: tidak memuat NIK/nomor identitas panjang.
        if (preg_match('/\b\d{16}\b/', $body)) {
            throw ValidationException::withMessages(['content_body' => 'Isi broadcast tidak boleh memuat nomor identitas (NIK).']);
        }

        if (($data['channel'] ?? $broadcast?->channel) === 'sms' && mb_strlen($body) > 160) {
            throw ValidationException::withMessages(['content_body' => 'SMS maksimal 160 karakter.']);
        }

        $broadcast ??= new Broadcast(['status' => BroadcastStatus::Draft, 'created_by' => $user->getKey()]);
        $broadcast->forceFill([
            'title' => $data['title'] ?? $broadcast->title,
            'channel' => $data['channel'] ?? $broadcast->channel,
            'audience_filter' => $data['audience_filter'] ?? $broadcast->audience_filter ?? [],
            'content_subject' => $data['content_subject'] ?? $broadcast->content_subject,
            'content_body' => $body,
        ])->save();

        return $broadcast->refresh();
    }

    public function audienceCount(Broadcast $broadcast): int
    {
        return $this->audience((array) $broadcast->audience_filter)->count();
    }

    public function schedule(Broadcast $broadcast, ?string $at): Broadcast
    {
        if ($broadcast->status !== BroadcastStatus::Draft) {
            throw new ConflictException('Broadcast sudah dijadwalkan.', 'INVALID_TRANSITION');
        }

        $when = $at ? \Carbon\CarbonImmutable::parse($at) : now();
        $broadcast->forceFill(['status' => BroadcastStatus::Scheduled, 'scheduled_at' => $when, 'recipients_count' => $this->audienceCount($broadcast)])->save();
        $this->outbox->record(OutboxRecorder::BROADCAST, $broadcast, ['broadcast_id' => $broadcast->getKey()], $when->isFuture() ? $when : null);

        return $broadcast->refresh();
    }

    public function cancel(Broadcast $broadcast): Broadcast
    {
        if (! in_array($broadcast->status, [BroadcastStatus::Draft, BroadcastStatus::Scheduled], true)) {
            throw new ConflictException('Broadcast sedang/sudah dikirim.', 'INVALID_TRANSITION');
        }

        $broadcast->forceFill(['status' => BroadcastStatus::Cancelled])->save();

        return $broadcast;
    }

    /**
     * Pengiriman bertahap (13 §A.4 batas broadcast: dipecah 1.000/batch).
     * Idempoten: dilanjutkan dari jumlah terkirim.
     */
    public function send(Broadcast $broadcast): void
    {
        if (! in_array($broadcast->status, [BroadcastStatus::Scheduled, BroadcastStatus::Sending], true)) {
            return;
        }

        $broadcast->forceFill(['status' => BroadcastStatus::Sending])->save();
        $sent = $broadcast->sent_count;
        $failed = $broadcast->failed_count;
        $offset = $sent + $failed;

        $this->audience((array) $broadcast->audience_filter)->orderBy('id')->skip($offset)->take(1000)->get()
            ->each(function (ApplicantAccount $account) use ($broadcast, &$sent, &$failed): void {
                $failures = $this->notifier->sendToApplicant($account, 'pmb.broadcast', [
                    'subject' => (string) $broadcast->content_subject,
                    'body' => (string) $broadcast->content_body,
                ], [], [$broadcast->channel === 'sms' ? 'sms' : 'mail', 'database']);
                $failures === [] ? $sent++ : $failed++;
            });

        $done = ($sent + $failed) >= $broadcast->recipients_count;
        $broadcast->forceFill(['sent_count' => $sent, 'failed_count' => $failed, 'status' => $done ? BroadcastStatus::Sent : BroadcastStatus::Sending])->save();

        if (! $done) {
            $this->outbox->record(OutboxRecorder::BROADCAST, $broadcast, ['broadcast_id' => $broadcast->getKey()], now()->addMinute());
        }
    }

    /**
     * Segmen pendaftar: periode/gelombang/jalur/status; `marketing` = hanya
     * akun dengan consent pemasaran aktif (13 §A.4 preferensi).
     *
     * @param  array<string, mixed>  $filter
     * @return \Illuminate\Database\Eloquent\Builder<ApplicantAccount>
     */
    public function audience(array $filter): \Illuminate\Database\Eloquent\Builder
    {
        $applicationFilter = fn ($q) => $q
            ->when($filter['period_id'] ?? null, fn ($a, $v) => $a->where('period_id', $v))
            ->when($filter['wave_id'] ?? null, fn ($a, $v) => $a->where('wave_id', $v))
            ->when($filter['admission_path_id'] ?? null, fn ($a, $v) => $a->where('admission_path_id', $v))
            ->when($filter['form_status'] ?? null, fn ($a, $v) => $a->whereIn('form_status', (array) $v))
            ->when($filter['verification_status'] ?? null, fn ($a, $v) => $a->whereIn('verification_status', (array) $v))
            ->when($filter['registration_payment_status'] ?? null, fn ($a, $v) => $a->whereIn('registration_payment_status', (array) $v))
            ->when($filter['decision'] ?? null, fn ($a, $v) => $a->whereHas('currentDecision', fn ($d) => $d->whereIn('decision', (array) $v)->where('publication_status', 'published')))
            ->when($filter['re_registration_status'] ?? null, fn ($a, $v) => $a->whereHas('reRegistration', fn ($r) => $r->whereIn('status', (array) $v)));

        $hasApplicationFilter = collect(['period_id', 'wave_id', 'admission_path_id', 'form_status', 'verification_status', 'registration_payment_status', 'decision', 're_registration_status'])
            ->contains(fn ($key) => ! empty($filter[$key]));

        return ApplicantAccount::query()->whereNull('anonymized_at')->whereNotIn('status', ['deactivated', 'suspended'])
            ->when($hasApplicationFilter, fn ($q) => $q->whereHas('applications', $applicationFilter))
            ->when(! empty($filter['marketing']), fn ($q) => $q->whereHas('consents', fn ($c) => $c->where('consent_type', ConsentType::MarketingOptional->value)->whereNull('revoked_at')));
    }
}
