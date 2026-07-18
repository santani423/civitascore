<?php

use App\Models\User;
use Modules\Auth\Models\UserDevice;
use Modules\Auth\Notifications\NewDeviceDetected;

test('a notification using HasUlidId gets a 26-character ulid id, not a 36-character uuid', function () {
    $device = UserDevice::factory()->make();

    $notification = new NewDeviceDetected($device);

    expect($notification->id)->toHaveLength(26);
});

test('sending a notification persists a ulid-shaped id in the notifications table', function () {
    $user = User::factory()->create();
    $device = UserDevice::factory()->create(['user_id' => $user->id]);

    $user->notify(new NewDeviceDetected($device));

    $row = $user->notifications()->first();

    expect($row)->not->toBeNull();
    expect($row->id)->toHaveLength(26);
});
