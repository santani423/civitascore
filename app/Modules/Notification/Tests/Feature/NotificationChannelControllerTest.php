<?php

use Modules\Notification\Enums\NotificationChannel;
use Modules\Notification\Models\NotificationChannelConfig;

test('enabling a channel that has a real delivery implementation succeeds', function () {
    actingAsUserWithPermissions(['notification_channels.update']);

    $channel = NotificationChannelConfig::factory()->create([
        'code' => NotificationChannel::Mail,
        'is_enabled' => false,
    ]);

    $response = $this->putJson("/api/v1/notification-channels/{$channel->id}", ['is_enabled' => true]);

    $response->assertApiSuccess();
    expect($channel->refresh()->is_enabled)->toBeTrue();
});

test('enabling a channel with no real delivery implementation is rejected', function () {
    actingAsUserWithPermissions(['notification_channels.update']);

    $channel = NotificationChannelConfig::factory()->create([
        'code' => NotificationChannel::Whatsapp,
        'is_enabled' => false,
    ]);

    $response = $this->putJson("/api/v1/notification-channels/{$channel->id}", ['is_enabled' => true]);

    $response->assertStatus(422);
    expect($channel->refresh()->is_enabled)->toBeFalse();
});

test('disabling an unimplemented channel is still allowed', function () {
    actingAsUserWithPermissions(['notification_channels.update']);

    $channel = NotificationChannelConfig::factory()->create([
        'code' => NotificationChannel::Sms,
        'is_enabled' => false,
    ]);

    $response = $this->putJson("/api/v1/notification-channels/{$channel->id}", ['is_enabled' => false]);

    $response->assertApiSuccess();
});
