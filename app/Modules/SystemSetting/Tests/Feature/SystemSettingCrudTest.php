<?php

use Modules\SystemSetting\Enums\SettingValueType;
use Modules\SystemSetting\Models\SystemSetting;

test('a permitted user can update a system setting value', function () {
    actingAsUserWithPermissions(['system_settings.read', 'system_settings.update']);

    $setting = SystemSetting::factory()->create([
        'key' => 'demo.max_items',
        'value' => '10',
        'type' => SettingValueType::Integer,
    ]);

    $response = $this->putJson("/api/v1/system-settings/{$setting->id}", ['value' => '25']);

    $response->assertApiSuccess();
    expect($response->json('data.value'))->toBe(25);
    expect($setting->refresh()->value)->toBe('25');
});

test('updating a system setting without permission is forbidden', function () {
    actingAsUserWithPermissions([]);

    $setting = SystemSetting::factory()->create();

    $response = $this->putJson("/api/v1/system-settings/{$setting->id}", ['value' => 'x']);

    $response->assertApiError(403);
});

test('updating a system setting requires a value', function () {
    actingAsUserWithPermissions(['system_settings.update']);

    $setting = SystemSetting::factory()->create();

    $response = $this->putJson("/api/v1/system-settings/{$setting->id}", []);

    $response->assertApiError(422);
});
