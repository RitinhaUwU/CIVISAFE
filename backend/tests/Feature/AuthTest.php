<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
});

it('logs in successfully', function () {

    $response = $this->postJson('/api/v1/login', [
        'email' => 'admin@example.com',
        'password' => 'password',
    ]);

    $response
        ->assertStatus(200)
        ->assertJsonStructure([
            'token'
        ]);
});

it('fails login with wrong password', function () {

    $response = $this->postJson('/api/v1/login', [
        'email' => 'admin@example.com',
        'password' => 'wrongpassword',
    ]);

    $response->assertStatus(422);
});

it('fails login with email', function () {

    $response = $this->postJson('/api/v1/login', [
        'email' => 'admin',
        'password' => 'password',
    ]);

    $response->assertStatus(422);
});

it('fails login when user is locked', function () {

    $response = $this->postJson('/api/v1/login', [
        'email' => 'manager@example.com',
        'password' => 'password',
    ]);

    $response->assertStatus(401);
});
