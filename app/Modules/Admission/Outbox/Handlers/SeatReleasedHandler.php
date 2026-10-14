<?php

namespace Modules\Admission\Outbox\Handlers;

use Modules\Admission\Models\OutboxEvent;
use Modules\Admission\Outbox\OutboxHandler;
use Modules\Admission\Services\Announcement\DecisionService;

class SeatReleasedHandler implements OutboxHandler
{
    public function __construct(private readonly DecisionService $decisions) {}

    public function handle(OutboxEvent $event): void
    {
        $this->decisions->autoPromote((string) $event->payload['quota_id']);
    }
}
