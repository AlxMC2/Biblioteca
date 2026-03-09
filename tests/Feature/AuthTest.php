<?php

use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

it('logs in with correct credentials', function () {
    $user = User::factory()->create([
        'password' => bcrypt('test123'),
    ]);

    $this->postJson('/api/v1/login', [
        'email' => $user->email,
        'password' => 'test123',
    ])
        ->assertOk()
        ->assertJsonStructure(['access_token', 'token_type', 'user']);
});

it('fails login with incorrect password', function () {
    $user = User::factory()->create([
        'password' => bcrypt('test123'),
    ]);

    $this->postJson('/api/v1/login', [
        'email' => $user->email,
        'password' => 'wrong_password',
    ])
        ->assertUnauthorized();
});

it('logs out and deletes all tokens', function () {
    $user  = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)
         ->postJson('/api/v1/logout')
         ->assertOk();

    expect(PersonalAccessToken::count())->toBe(0);
});

it('revoked token cannot access profile', function () {
    $user  = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;
    $user->tokens()->delete();

    $this->withToken($token)
         ->getJson('/api/v1/profile')
         ->assertUnauthorized();
});

it('fails logout without authentication', function () {
    $this->postJson('/api/v1/logout')
        ->assertUnauthorized();
});

it('returns profile data without exposing password', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson('/api/v1/profile')
        ->assertOk()
        ->assertJsonStructure(['user' => ['id', 'name', 'email']])
        ->assertJsonMissing(['password']);
});