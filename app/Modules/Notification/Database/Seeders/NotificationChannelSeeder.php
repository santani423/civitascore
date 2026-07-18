<?php

namespace Modules\Notification\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Notification\Enums\NotificationChannel;
use Modules\Notification\Models\NotificationChannelConfig;

/**
 * Every real Notification class in Phase 1 (NewDeviceDetected,
 * ApprovalStepAssigned, ApprovalRequestDecided) sends via database+mail,
 * and every send is gated by this table through LogNotificationDispatch —
 * push/whatsapp/sms stay disabled since no driver is wired up yet.
 */
class NotificationChannelSeeder extends Seeder
{
    public function run(): void
    {
        $enabled = [NotificationChannel::Database, NotificationChannel::Mail];

        foreach (NotificationChannel::cases() as $channel) {
            NotificationChannelConfig::query()->updateOrCreate(
                ['code' => $channel],
                ['name' => ucfirst($channel->value), 'is_enabled' => in_array($channel, $enabled, true)],
            );
        }
    }
}
