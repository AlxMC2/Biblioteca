<?php

use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

test('iniciar sesion con credenciales correctas', function () {
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

test('fallo en el inicio de sesion con contraseña incorrecta', function () {
    $user = User::factory()->create([
        'password' => bcrypt('test123'),
    ]);

    $this->postJson('/api/v1/login', [
        'email' => $user->email,
        'password' => 'wrong_password',
    ])
        ->assertUnauthorized();
});

test('el cierre de sesión elimina todos los tokens de acceso', function () {
    $user  = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)
         ->postJson('/api/v1/logout')
         ->assertOk();

    expect(PersonalAccessToken::count())->toBe(0);
});

test('el usuario no puede acceder con un token revocado', function () {
    $user  = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;
    $user->tokens()->delete();

    $this->withToken($token)
         ->getJson('/api/v1/profile')
         ->assertUnauthorized();
});

test('fallo en el cierre de sesión si no hay ningún usuario autenticado', function () {
    $this->postJson('/api/v1/logout')
        ->assertUnauthorized();
});

test('retorno de los datos del perfil sin exponer la contraseña', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson('/api/v1/profile')
        ->assertOk()
        ->assertJsonStructure(['user' => ['id', 'name', 'email']])
        ->assertJsonMissing(['password']);
});