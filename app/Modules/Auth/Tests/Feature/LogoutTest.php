<?php

use App\Models\User;
use Modules\Auth\Models\UserSession;

test('logging out revokes the current session and deletes the token', function () {
    $user = User::factory()->create();

    $login = $this->postJson('/api/v1/login', ['email' => $user->email, 'password' => 'password']);
    $token = $login->json('data.token');

    $response = $this->withHeader('Authorization', "Bearer {$token}")->postJson('/api/v1/logout');

    $response->assertApiSuccess();
    expect($user->tokens()->count())->toBe(0);
    expect(UserSession::query()->where('user_id', $user->id)->whereNull('revoked_at')->count())->toBe(0);
});

test('an unauthenticated request cannot access a protected endpoint', function () {
    $response = $this->getJson('/api/v1/me');

    $response->assertApiError(401);
});
