<?php

namespace Modules\Admission\Outbox\Handlers;

use Modules\Admission\Models\OutboxEvent;
use Modules\Admission\Outbox\OutboxHandler;
use Modules\Admission\Services\Conversion\ConversionService;

/** Konversi berjalan dengan retry/lease sendiri — event outbox cukup memicu. */
class ConversionRequestedHandler implements OutboxHandler
{
    public function __construct(private readonly ConversionService $conversions) {}

    public function handle(OutboxEvent $event): void
    {
        $this->conversions->process((string) $event->payload['conversion_id']);
    }
}
