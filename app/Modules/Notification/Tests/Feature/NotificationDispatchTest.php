<?php

use App\Models\User;
use Modules\Notification\Enums\NotificationChannel;
use Modules\Notification\Enums\NotificationLogStatus;
use Modules\Notification\Models\NotificationChannelConfig;
use Modules\Notification\Models\NotificationLog;
use Modules\Notification\Models\NotificationTemplate;
use Modules\Notification\Models\UserNotificationPreference;
use Modules\Notification\Services\NotificationDispatcher;

test('dispatching a templated notification logs a Sent entry on an enabled channel', function () {
    NotificationTemplate::factory()->create([
        'event_key' => 'demo.dispatch',
        'channel' => NotificationChannel::Database,
        'body_template' => 'Halo {{name}}.',
    ]);

    $user = User::factory()->create();

    app(NotificationDispatcher::class)->dispatch($user, 'demo.dispatch', [NotificationChannel::Database], ['name' => $user->name]);

    $log = NotificationLog::query()
        ->where('notifiable_id', $user->id)
        ->where('channel', NotificationChannel::Database)
        ->first();

    expect($log)->not->toBeNull();
    expect($log->status)->toBe(NotificationLogStatus::Sent);
    expect($user->notifications()->first()->data['message'])->toBe("Halo {$user->name}.");
});

test('a disabled channel causes the notification to be skipped and logged as Skipped', function () {
    NotificationChannelConfig::query()->updateOrCreate(['code' => NotificationChannel::Database], ['name' => 'Database', 'is_enabled' => false]);

    $user = User::factory()->create();

    app(NotificationDispatcher::class)->dispatch($user, 'demo.disabled_channel', [NotificationChannel::Database]);

    $log = NotificationLog::query()->where('notifiable_id', $user->id)->first();

    expect($log->status)->toBe(NotificationLogStatus::Skipped);
    expect($user->notifications()->count())->toBe(0);
});

test('a user who opted out of a channel does not receive the notification', function () {
    $user = User::factory()->create();
    UserNotificationPreference::factory()->create([
        'user_id' => $user->id,
        'channel' => NotificationChannel::Database,
        'notification_type' => '*',
        'is_enabled' => false,
    ]);

    app(NotificationDispatcher::class)->dispatch($user, 'demo.opt_out', [NotificationChannel::Database]);

    expect($user->notifications()->count())->toBe(0);

    $log = NotificationLog::query()->where('notifiable_id', $user->id)->first();
    expect($log->status)->toBe(NotificationLogStatus::Skipped);
});
