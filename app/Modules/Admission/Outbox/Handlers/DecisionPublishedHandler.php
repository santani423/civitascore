<?php

namespace Modules\Admission\Outbox\Handlers;

use Modules\Admission\Models\AdmissionDecision;
use Modules\Admission\Models\OutboxEvent;
use Modules\Admission\Outbox\OutboxHandler;
use Modules\Admission\Services\Announcement\AcceptanceLetterService;

class DecisionPublishedHandler implements OutboxHandler
{
    public function __construct(private readonly AcceptanceLetterService $letters) {}

    public function handle(OutboxEvent $event): void
    {
        $decision = AdmissionDecision::query()->find($event->payload['decision_id']);

        if ($decision !== null) {
            $this->letters->issue($decision);
        }
    }
}
