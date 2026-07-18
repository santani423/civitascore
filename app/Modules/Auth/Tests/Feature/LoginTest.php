<?php

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Auth\Enums\LoginHistoryStatus;
use Modules\Auth\Models\LoginHistory;
use Modules\Auth\Models\UserSession;

test('a user can log in with valid credentials and receives a token', function () {
    $user = User::factory()->create();

    $response = $this->postJson('/api/v1/login', [
        'email' => $user->email,
        'password' => 'password',
        'device_identifier' => 'device-123',
    ]);

    $response->assertApiSuccess();
    expect($response->json('data.token'))->toBeString()->not->toBeEmpty();

    expect(LoginHistory::query()->where('user_id', $user->id)->where('status', LoginHistoryStatus::Success)->exists())->toBeTrue();
    expect(UserSession::query()->where('user_id', $user->id)->exists())->toBeTrue();
});

test('login fails with the wrong password and records a failed login history', function () {
    $user = User::factory()->create();

    $response = $this->postJson('/api/v1/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertApiError(422);

    expect(LoginHistory::query()->where('user_id', $user->id)->where('status', LoginHistoryStatus::Failed)->exists())->toBeTrue();
});

test('an inactive account cannot log in', function () {
    $user = User::factory()->create(['is_active' => false]);

    $response = $this->postJson('/api/v1/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertApiError(403);
});

test('login is rate limited after too many failed attempts', function () {
    $user = User::factory()->create();

    RateLimiter::clear('login:'.mb_strtolower($user->email).'|127.0.0.1');

    for ($i = 0; $i < 5; $i++) {
        $this->postJson('/api/v1/login', ['email' => $user->email, 'password' => 'wrong-password']);
    }

    $response = $this->postJson('/api/v1/login', ['email' => $user->email, 'password' => 'wrong-password']);

    $response->assertApiError(429);
});
