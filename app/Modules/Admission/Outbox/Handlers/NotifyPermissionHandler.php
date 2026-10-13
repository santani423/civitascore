<?php

namespace Modules\Admission\Outbox\Handlers;

use Modules\Admission\Models\OutboxEvent;
use Modules\Admission\Outbox\OutboxHandler;
use Modules\Admission\Support\PmbNotifier;

class NotifyPermissionHandler implements OutboxHandler
{
    public function __construct(private readonly PmbNotifier $notifier) {}

    public function handle(OutboxEvent $event): void
    {
        $payload = (array) $event->payload;
        $this->notifier->sendToPermissionHolders((string) $event->university_id, (string) $payload['permission'], (string) $payload['event_key'], (array) ($payload['placeholders'] ?? []), (array) ($payload['data'] ?? []));
    }
}
