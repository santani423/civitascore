<?php

namespace Modules\Admission\Payments;

final class WebhookEvent
{
    /** @param  array<string, mixed>  $payload */
    public function __construct(
        public readonly string $eventId,
        public readonly string $providerReference,
        public readonly string $status,
        public readonly array $payload,
    ) {}
}
