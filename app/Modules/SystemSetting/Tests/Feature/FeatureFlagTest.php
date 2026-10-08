<?php

use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Modules\Academic\Support\AcademicFeature;
use Modules\SystemSetting\Database\Seeders\FeatureFlagSeeder;
use Modules\SystemSetting\Models\FeatureFlag;
use Modules\SystemSetting\Services\FeatureFlagService;
use Modules\Tenancy\Models\University;
use Modules\Tenancy\Models\UniversityFeatureFlag;

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

/*
| Override per universitas (university_feature_flags, Tahap 0.5): override
| tenant menang atas nilai global, universitas lain tidak terpengaruh, dan
| perubahan dari konteks tenant tidak pernah menyentuh nilai global.
*/

test('a university override wins over the global value, without affecting other universities', function () {
    $flag = FeatureFlag::factory()->create(['key' => 'demo.rollout', 'is_enabled' => false]);
    $pilot = University::factory()->create();
    $other = University::factory()->create();
    $flags = app(FeatureFlagService::class);

    expect($flags->isEnabled('demo.rollout', $pilot->id))->toBeFalse();

    $flags->setOverride($flag, $pilot->id, true);

    expect($flags->isEnabled('demo.rollout', $pilot->id))->toBeTrue()
        ->and($flags->isEnabled('demo.rollout', $other->id))->toBeFalse()
        ->and($flags->isEnabled('demo.rollout'))->toBeFalse()
        ->and($flags->overrideFor('demo.rollout', $other->id))->toBeNull();

    // Tenant aktif dipakai bila universitas tidak disebut.
    app(TenantContext::class)->setUniversityId($pilot->id);
    expect($flags->isEnabled('demo.rollout'))->toBeTrue();
    app(TenantContext::class)->setUniversityId(null);

    // Override "mati" juga menang atas global yang menyala.
    $flags->toggle($flag, true, User::factory()->create());
    $flags->setOverride($flag, $other->id, false);

    expect($flags->isEnabled('demo.rollout', $other->id))->toBeFalse()
        ->and($flags->isEnabled('demo.rollout', University::factory()->create()->id))->toBeTrue();
});

test('overrides written outside the service are picked up immediately', function () {
    FeatureFlag::factory()->create(['key' => 'demo.seeded', 'is_enabled' => false]);
    $university = University::factory()->create();
    $flags = app(FeatureFlagService::class);

    expect($flags->isEnabled('demo.seeded', $university->id))->toBeFalse();

    $override = UniversityFeatureFlag::query()->create(['university_id' => $university->id, 'key' => 'demo.seeded', 'is_enabled' => true]);
    expect($flags->isEnabled('demo.seeded', $university->id))->toBeTrue();

    $override->delete();
    expect($flags->isEnabled('demo.seeded', $university->id))->toBeFalse();
});

test('a tenant administrator only changes the flag for their own university', function () {
    $flag = FeatureFlag::factory()->create(['key' => 'demo.tenant', 'is_enabled' => false]);
    $university = University::factory()->create();
    $other = University::factory()->create();
    actingAsUserWithUniversityPermissions($university, ['feature_flags.read', 'feature_flags.update']);

    $this->withHeader('X-University-ID', $university->id)
        ->putJson("/api/v1/feature-flags/{$flag->id}", ['is_enabled' => true])
        ->assertApiSuccess()
        ->assertJsonPath('data.is_enabled', true)
        ->assertJsonPath('data.default_enabled', false)
        ->assertJsonPath('data.university_override', true);

    expect($flag->refresh()->is_enabled)->toBeFalse()
        ->and(app(FeatureFlagService::class)->isEnabled('demo.tenant', $university->id))->toBeTrue()
        ->and(app(FeatureFlagService::class)->isEnabled('demo.tenant', $other->id))->toBeFalse();

    $this->withHeader('X-University-ID', $university->id)
        ->getJson('/api/v1/feature-flags?search=demo.tenant')
        ->assertApiSuccess()
        ->assertJsonPath('data.0.is_enabled', true)
        ->assertJsonPath('data.0.university_override', true);

    $this->withHeader('X-University-ID', $university->id)
        ->deleteJson("/api/v1/feature-flags/{$flag->id}/override")
        ->assertApiSuccess()
        ->assertJsonPath('data.is_enabled', false)
        ->assertJsonPath('data.university_override', null);

    expect(app(FeatureFlagService::class)->isEnabled('demo.tenant', $university->id))->toBeFalse()
        ->and(UniversityFeatureFlag::query()->count())->toBe(0);
});

test('clearing an override needs a university context', function () {
    $flag = FeatureFlag::factory()->create(['key' => 'demo.platform']);
    actingAsUserWithPermissions(['feature_flags.update']);

    $this->deleteJson("/api/v1/feature-flags/{$flag->id}/override")->assertApiError(409);
});

test('the academic flags exist and are disabled by default', function () {
    $this->seed(FeatureFlagSeeder::class);

    expect(FeatureFlag::query()->where('key', AcademicFeature::LECTURER_OWNERSHIP)->value('is_enabled'))->toBeFalse()
        ->and(FeatureFlag::query()->where('key', AcademicFeature::SHORT_TERM)->value('is_enabled'))->toBeFalse()
        ->and(app(AcademicFeature::class)->lecturerOwnershipEnforced())->toBeFalse();

    // Menjalankan ulang seeder tidak mengubah nilai global yang sudah diatur.
    FeatureFlag::query()->where('key', AcademicFeature::SHORT_TERM)->update(['is_enabled' => true]);
    $this->seed(FeatureFlagSeeder::class);

    expect(FeatureFlag::query()->where('key', AcademicFeature::SHORT_TERM)->value('is_enabled'))->toBeTrue();
});
