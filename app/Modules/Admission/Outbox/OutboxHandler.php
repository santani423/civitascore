<?php

namespace Modules\Admission\Outbox;

use Modules\Admission\Models\OutboxEvent;

/**
 * Handler event outbox — WAJIB idempoten: event dapat diproses ulang setelah
 * kegagalan sebagian (K.6, 13 §A.4).
 */
interface OutboxHandler
{
    public function handle(OutboxEvent $event): void;
}
