<?php

use App\Models\User;

test('api users receive jwt and refresh tokens', function () {
    $user = User::factory()->create();

    $response = $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertOk()->assertJsonStructure([
        'access_token',
        'refresh_token',
        'token_type',
        'expires_in',
    ]);
});

test('api rejects invalid credentials', function () {
    $user = User::factory()->create();

    $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ])->assertUnauthorized();
});

test('api refresh tokens rotate and authenticate the user', function () {
    $user = User::factory()->create();
    $login = $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'password']);
    $refreshToken = $login->json('refresh_token');

    $refresh = $this->postJson('/api/auth/refresh', ['refresh_token' => $refreshToken]);

    $refresh->assertOk();
    expect($refresh->json('refresh_token'))->not->toBe($refreshToken);
    $this->postJson('/api/auth/refresh', ['refresh_token' => $refreshToken])->assertUnauthorized();
    $this->withToken($refresh->json('access_token'))->getJson('/api/auth/me')->assertOk()->assertJsonPath('user.id', $user->id);
});

test('api rejects invalid jwt access tokens', function () {
    $this->withToken('invalid.token.value')->getJson('/api/auth/me')->assertUnauthorized();
});
