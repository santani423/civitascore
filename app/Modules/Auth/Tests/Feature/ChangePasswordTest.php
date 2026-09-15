<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('a user can change their password and it clears must_change_password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('15082004'),
        'must_change_password' => true,
    ]);

    $login = $this->postJson('/api/v1/login', ['email' => $user->email, 'password' => '15082004']);
    $token = $login->json('data.token');

    $response = $this->withHeader('Authorization', "Bearer {$token}")->postJson('/api/v1/change-password', [
        'current_password' => '15082004',
        'password' => 'new-strong-password',
        'password_confirmation' => 'new-strong-password',
    ]);

    $response->assertApiSuccess();

    $user->refresh();
    expect($user->must_change_password)->toBeFalse();
    expect($user->password_changed_at)->not->toBeNull();
    expect(Hash::check('new-strong-password', $user->password))->toBeTrue();
    expect(Hash::check('15082004', $user->password))->toBeFalse();
});

test('changing password fails when the current password is wrong', function () {
    $user = User::factory()->create(['password' => Hash::make('15082004')]);

    $login = $this->postJson('/api/v1/login', ['email' => $user->email, 'password' => '15082004']);
    $token = $login->json('data.token');

    $response = $this->withHeader('Authorization', "Bearer {$token}")->postJson('/api/v1/change-password', [
        'current_password' => 'wrong-password',
        'password' => 'new-strong-password',
        'password_confirmation' => 'new-strong-password',
    ]);

    $response->assertApiError(422);
});
