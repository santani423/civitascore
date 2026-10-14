<?php

namespace Modules\Admission\Outbox;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Modules\Admission\Enums\OutboxStatus;
use Modules\Admission\Jobs\ProcessOutboxEvents;
use Modules\Admission\Models\ApplicantAccount;
use Modules\Admission\Models\OutboxEvent;

/**
 * Menulis event efek samping di transaksi yang sama dengan perubahan data
 * (KD-06, "Outbox" di glosarium). Setelah commit, job pemroses dikirim
 * (afterCommit); scheduler `pmb:process-outbox` menjadi jaring pengaman bila
 * worker antrean tidak berjalan (lingkungan shared hosting).
 */
class OutboxRecorder
{
    public const NOTIFY_APPLICANT = 'notification.applicant';

    public const NOTIFY_PERMISSION = 'notification.permission';

    public const CONVERSION_REQUESTED = 'student.conversion_requested';

    public const STUDENT_ENROLLED = 'student.enrolled';

    public const DECISION_PUBLISHED = 'decision.published';

    public const EXAM_CARD = 'exam.card';

    public const SEAT_RELEASED = 'quota.seat_released';

    public const BROADCAST = 'broadcast.send';

    public const EXPORT = 'export.generate';

    public const DOCUMENT_SCAN = 'document.scan';

    /** Placeholder berisi rahasia sekali pakai — tidak pernah disimpan polos (08 §6). */
    public const SECRET_PLACEHOLDERS = ['otp_code', 'reset_url', 'verification_url'];

    /**
     * @param  array<string, mixed>  $payload
     */
    public function record(string $type, Model $aggregate, array $payload, ?CarbonInterface $availableAt = null, ?string $universityId = null): OutboxEvent
    {
        $payload = self::seal($payload);

        $event = OutboxEvent::query()->create([
            'university_id' => $universityId ?? $aggregate->getAttribute('university_id'),
            'event_type' => $type,
            'aggregate_type' => class_basename($aggregate),
            'aggregate_id' => (string) $aggregate->getKey(),
            'payload' => $payload,
            'status' => OutboxStatus::Pending,
            'attempts' => 0,
            'available_at' => $availableAt ?? now(),
        ]);

        if ($availableAt === null || $availableAt->isPast()) {
            if (DB::transactionLevel() > 0) {
                DB::afterCommit(fn () => ProcessOutboxEvents::dispatch($event->getKey()));
            } else {
                ProcessOutboxEvents::dispatch($event->getKey());
            }
        }

        return $event;
    }

    /**
     * Memindahkan placeholder rahasia ke `sealed` terenkripsi (APP_KEY).
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public static function seal(array $payload): array
    {
        $placeholders = (array) ($payload['placeholders'] ?? []);
        $secrets = array_intersect_key($placeholders, array_flip(self::SECRET_PLACEHOLDERS));

        if ($secrets === []) {
            return $payload;
        }

        $payload['placeholders'] = array_diff_key($placeholders, $secrets);
        $payload['sealed'] = Crypt::encryptString(json_encode($secrets, JSON_THROW_ON_ERROR));

        return $payload;
    }

    /**
     * Placeholder lengkap (termasuk rahasia yang didekripsi) untuk handler.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public static function placeholders(array $payload): array
    {
        $placeholders = (array) ($payload['placeholders'] ?? []);

        if (! empty($payload['sealed'])) {
            $placeholders = [...$placeholders, ...(array) json_decode(Crypt::decryptString((string) $payload['sealed']), true)];
        }

        return $placeholders;
    }

    /**
     * @param  array<string, string|int|float|null>  $placeholders
     * @param  array<string, mixed>  $data
     */
    public function notifyApplicant(ApplicantAccount $account, string $eventKey, array $placeholders = [], array $data = [], ?Model $aggregate = null): OutboxEvent
    {
        return $this->record(self::NOTIFY_APPLICANT, $aggregate ?? $account, [
            'account_id' => (string) $account->getKey(),
            'event_key' => $eventKey,
            'placeholders' => $placeholders,
            'data' => $data,
        ], universityId: (string) $account->university_id);
    }

    /**
     * @param  array<string, string|int|float|null>  $placeholders
     * @param  array<string, mixed>  $data
     */
    public function notifyPermission(Model $aggregate, string $permission, string $eventKey, array $placeholders = [], array $data = []): OutboxEvent
    {
        return $this->record(self::NOTIFY_PERMISSION, $aggregate, [
            'permission' => $permission,
            'event_key' => $eventKey,
            'placeholders' => $placeholders,
            'data' => $data,
        ]);
    }
}
