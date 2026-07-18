<?php

namespace Modules\Auth\Listeners;

use Modules\AuditLog\Services\AuditLogService;
use Modules\Auth\Events\NewDeviceDetected;

class RecordNewDeviceLogin
{
    public function __construct(private readonly AuditLogService $auditLog) {}

    public function handle(NewDeviceDetected $event): void
    {
        $this->auditLog->log(
            logName: 'auth',
            description: "Login dari perangkat baru: {$event->device->device_name}",
            subject: $event->user,
            properties: [
                'device_id' => $event->device->id,
                'platform' => $event->device->platform,
            ],
        );
    }
}
