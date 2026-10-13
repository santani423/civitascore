<?php

namespace Modules\Admission\Outbox\Handlers;

use Modules\Admission\Models\Broadcast;
use Modules\Admission\Models\OutboxEvent;
use Modules\Admission\Outbox\OutboxHandler;
use Modules\Admission\Services\Communication\CommunicationService;

class BroadcastHandler implements OutboxHandler
{
    public function __construct(private readonly CommunicationService $communication) {}

    public function handle(OutboxEvent $event): void
    {
        $broadcast = Broadcast::query()->find($event->payload['broadcast_id']);

        if ($broadcast !== null) {
            $this->communication->send($broadcast);
        }
    }
}
