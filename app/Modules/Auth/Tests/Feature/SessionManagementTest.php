<?php

use App\Models\User;
use Modules\Auth\Models\UserSession;

test('a user can list and revoke their own session', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $session = UserSession::factory()->create(['user_id' => $user->id]);

    $listResponse = $this->getJson('/api/v1/sessions');
    $listResponse->assertApiSuccess();

    $response = $this->deleteJson("/api/v1/sessions/{$session->id}");

    $response->assertApiSuccess();
    expect($session->refresh()->revoked_at)->not->toBeNull();
});

test('a user cannot revoke another user\'s session', function () {
    $owner = User::factory()->create();
    $session = UserSession::factory()->create(['user_id' => $owner->id]);

    $intruder = User::factory()->create();
    $this->actingAs($intruder);

    $response = $this->deleteJson("/api/v1/sessions/{$session->id}");

    $response->assertApiError(403);
});
