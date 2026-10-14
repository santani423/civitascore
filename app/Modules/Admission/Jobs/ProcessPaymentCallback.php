<?php

namespace Modules\Admission\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Admission\Models\PaymentCallback;
use Modules\Admission\Services\Payment\WebhookService;

class ProcessPaymentCallback implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(public readonly string $callbackId) {}

    public function handle(WebhookService $webhooks): void
    {
        $callback = PaymentCallback::query()->find($this->callbackId);

        if ($callback !== null) {
            $webhooks->process($callback);
        }
    }
}
