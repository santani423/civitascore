<?php

use Modules\SystemSetting\Models\FeatureFlag;
use Modules\SystemSetting\Services\FeatureFlagService;

test('toggling a feature flag off is immediately reflected by FeatureFlagService', function () {
    $flag = FeatureFlag::factory()->create(['key' => 'demo.flag', 'is_enabled' => true]);

    actingAsUserWithPermissions(['feature_flags.read', 'feature_flags.update']);

    expect(app(FeatureFlagService::class)->isEnabled('demo.flag'))->toBeTrue();

    $response = $this->putJson("/api/v1/feature-flags/{$flag->id}", ['is_enabled' => false]);

    $response->assertApiSuccess();
    expect(app(FeatureFlagService::class)->isEnabled('demo.flag'))->toBeFalse();
});

test('an unknown feature flag key defaults to disabled', function () {
    expect(app(FeatureFlagService::class)->isEnabled('does.not.exist'))->toBeFalse();
});
