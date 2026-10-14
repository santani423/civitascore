<?php

namespace Modules\Admission\Outbox;

use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Admission\Enums\OutboxStatus;
use Modules\Admission\Models\OutboxEvent;
use Throwable;

/**
 * Memproses event outbox: lease per event (status tetap `pending` dengan
 * attempts+1 dan available_at maju sehingga proses paralel tidak mengambil
 * event yang sama), handler idempoten, retry eksponensial 1/5/15/60 menit,
 * maksimal 6 percobaan lalu `failed` + alert operasional.
 */
class OutboxProcessor
{
    private const BACKOFF_MINUTES = [1, 5, 15, 60, 60, 60];

    private const MAX_ATTEMPTS = 6;

    private const LEASE_MINUTES = 5;

    /** @param  array<string, class-string<OutboxHandler>>  $handlers */
    public function __construct(private readonly TenantContext $tenant, private readonly array $handlers) {}

    public function processPending(int $limit = 50): int
    {
        $ids = OutboxEvent::query()->withoutGlobalScopes()
            ->where('status', OutboxStatus::Pending->value)
            ->where('available_at', '<=', now())
            ->orderBy('available_at')
            ->limit($limit)
            ->pluck('id');

        $processed = 0;

        foreach ($ids as $id) {
            $processed += $this->processOne((string) $id) ? 1 : 0;
        }

        return $processed;
    }

    public function processOne(string $id): bool
    {
        $event = DB::transaction(function () use ($id): ?OutboxEvent {
            $event = OutboxEvent::query()->withoutGlobalScopes()->whereKey($id)->lockForUpdate()->first();

            if ($event === null || $event->status !== OutboxStatus::Pending || $event->available_at->isFuture()) {
                return null;
            }

            $event->forceFill([
                'attempts' => $event->attempts + 1,
                'available_at' => now()->addMinutes(self::LEASE_MINUTES),
            ])->save();

            return $event;
        });

        if ($event === null) {
            return false;
        }

        $previousTenant = $this->tenant->universityId();
        $this->tenant->setUniversityId((string) $event->university_id);

        try {
            $handlerClass = $this->handlers[$event->event_type] ?? null;

            if ($handlerClass === null) {
                throw new \RuntimeException("Tidak ada handler untuk event {$event->event_type}.");
            }

            app($handlerClass)->handle($event);

            $event->forceFill(['status' => OutboxStatus::Dispatched, 'dispatched_at' => now(), 'last_error' => null])->save();

            return true;
        } catch (Throwable $e) {
            $this->fail($event, $e);

            return false;
        } finally {
            $this->tenant->setUniversityId($previousTenant);
        }
    }

    private function fail(OutboxEvent $event, Throwable $e): void
    {
        $exhausted = $event->attempts >= self::MAX_ATTEMPTS;

        $event->forceFill([
            'status' => $exhausted ? OutboxStatus::Failed : OutboxStatus::Pending,
            'available_at' => now()->addMinutes(self::BACKOFF_MINUTES[min($event->attempts, count(self::BACKOFF_MINUTES)) - 1] ?? 60),
            'last_error' => mb_substr($e->getMessage(), 0, 2000),
        ])->save();

        Log::log($exhausted ? 'error' : 'warning', '[pmb-outbox] event gagal', [
            'event_id' => $event->id,
            'type' => $event->event_type,
            'attempts' => $event->attempts,
            'error' => $e->getMessage(),
        ]);
    }
}
