<?php

namespace Modules\Admission\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Admission\Outbox\OutboxProcessor;

class ProcessOutboxEvents implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(public readonly ?string $eventId = null) {}

    public function handle(OutboxProcessor $processor): void
    {
        $this->eventId !== null ? $processor->processOne($this->eventId) : $processor->processPending();
    }
}
