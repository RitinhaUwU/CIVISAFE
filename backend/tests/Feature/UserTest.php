<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
});

it('creates a user', function () {

    $admin = User::factory()->create([
        'locked' => false,
    ]);

    $admin->givePermissionTo('USERS_CREATE');

    Sanctum::actingAs($admin);

    $response = $this->postJson('/api/v1/users', [
        'name' => 'Test User',
        'email' => 'testUser@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'mobile' => '912345678',
        'locked' => false,
        'role' => 'admin',
    ]);

    $response->assertStatus(201);

    $this->assertDatabaseHas('users', [
        'email' => 'testUser@example.com',
    ]);
});

it('fails creating user without permission', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/users', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'mobile' => '912345678',
        'locked' => false,
        'role' => 'admin',
    ]);

    $response->assertStatus(403);
});

it('fails validation when name is missing', function () {

    $admin = User::factory()->create([
        'locked' => false,
    ]);

    $admin->givePermissionTo('USERS_CREATE');

    Sanctum::actingAs($admin);

    $response = $this->postJson('/api/v1/users', [
        'name' => '',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'mobile' => '912345678',
        'locked' => false,
        'role' => 'admin',
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

it('fails validation when email already exists', function () {

    $admin = User::factory()->create([
        'locked' => false,
    ]);

    $admin->givePermissionTo('USERS_CREATE');

    Sanctum::actingAs($admin);

    User::factory()->create([
        'email' => 'existing@example.com',
    ]);

    $response = $this->postJson('/api/v1/users', [
        'name' => 'Test User',
        'email' => 'existing@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'mobile' => '912345678',
        'locked' => false,
        'role' => 'admin',
    ]);

    $response->assertStatus(422);
});

it('fails validation when password is too short', function () {

    $admin = User::factory()->create([
        'locked' => false,
    ]);

    $admin->givePermissionTo('USERS_CREATE');

    Sanctum::actingAs($admin);

    $response = $this->postJson('/api/v1/users', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => '123',
        'password_confirmation' => '123',
        'mobile' => '912345678',
        'locked' => false,
        'role' => 'admin',
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

it('fails validation when password confirmation does not match', function () {

    $admin = User::factory()->create([
        'locked' => false,
    ]);

    $admin->givePermissionTo('USERS_CREATE');

    Sanctum::actingAs($admin);

    $response = $this->postJson('/api/v1/users', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'wrongpassword',
        'mobile' => '912345678',
        'locked' => false,
        'role' => 'admin',
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

it('fails validation with invalid mobile number', function () {

    $admin = User::factory()->create([
        'locked' => false,
    ]);

    $admin->givePermissionTo('USERS_CREATE');

    Sanctum::actingAs($admin);

    $response = $this->postJson('/api/v1/users', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'mobile' => 'abc123',
        'locked' => false,
        'role' => 'admin',
    ]);

    $response->assertStatus(422);
});

it('fails validation when locked is invalid', function () {

    $admin = User::factory()->create([
        'locked' => false,
    ]);

    $admin->givePermissionTo('USERS_CREATE');

    Sanctum::actingAs($admin);

    $response = $this->postJson('/api/v1/users', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'mobile' => '912345678',
        'locked' => 'invalid',
        'role' => 'admin',
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['locked']);
});

it('fails validation when role does not exist', function () {

    $admin = User::factory()->create([
        'locked' => false,
    ]);

    $admin->givePermissionTo('USERS_CREATE');

    Sanctum::actingAs($admin);

    $response = $this->postJson('/api/v1/users', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'mobile' => '912345678',
        'locked' => false,
        'role' => 'fake-role',
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['role']);
});

it('lists users', function () {

    $admin = User::factory()->create([
        'locked' => false,
    ]);

    $admin->givePermissionTo('USERS_VIEW_ANY');

    Sanctum::actingAs($admin);

    User::factory()->count(3)->create();

    $response = $this->getJson('/api/v1/users');

    $response
        ->assertStatus(200)
        ->assertJsonStructure([
            'data',
            'links',
            'meta',
        ]);
});

it('fails listing users without permission', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/v1/users');

    $response->assertStatus(403);
});

it('shows a user', function () {

    $admin = User::factory()->create([
        'locked' => false,
    ]);

    $admin->assignRole('admin');

    $user = User::factory()->create();

    Sanctum::actingAs($admin);

    $response = $this->getJson("/api/v1/users/{$user->id}");

    $response
        ->assertStatus(200)
        ->assertJsonFragment([
            'email' => $user->email,
        ]);
});

it('fails showing user without permission', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $targetUser = User::factory()->create();

    Sanctum::actingAs($user);

    $response = $this->getJson("/api/v1/users/{$targetUser->id}");

    $response->assertStatus(403);
});

it('shows own user with own permission', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('USERS_VIEW_OWN');

    Sanctum::actingAs($user);

    $response = $this->getJson("/api/v1/users/{$user->id}");

    $response
        ->assertStatus(200)
        ->assertJsonFragment([
            'id' => $user->id,
        ]);
});

it('fails showing another user with only own permission', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $otherUser = User::factory()->create();

    $user->givePermissionTo('USERS_VIEW_OWN');

    Sanctum::actingAs($user);

    $response = $this->getJson("/api/v1/users/{$otherUser->id}");

    $response->assertStatus(403);
});

it('updates a user', function () {

    $admin = User::factory()->create([
        'locked' => false,
    ]);

    $admin->givePermissionTo('USERS_UPDATE_ANY');

    $user = User::factory()->create();

    Sanctum::actingAs($admin);

    $response = $this->patchJson("/api/v1/users/{$user->id}", [
        'name' => 'Updated User',
    ]);

    $response->assertStatus(200);

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => 'Updated User',
    ]);
});

it('fails updating user without permission', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $targetUser = User::factory()->create();

    Sanctum::actingAs($user);

    $response = $this->patchJson("/api/v1/users/{$targetUser->id}", [
        'name' => 'Hacker',
    ]);

    $response->assertStatus(403);
});

it('updates own user with own permission', function () {

    $user = User::factory()->create([
        'locked' => false,
        'name' => 'Old Name',
    ]);

    $user->givePermissionTo('USERS_UPDATE_OWN');

    Sanctum::actingAs($user);

    $response = $this->patchJson("/api/v1/users/{$user->id}", [
        'name' => 'New Name',
    ]);

    $response
        ->assertStatus(200)
        ->assertJsonFragment([
            'name' => 'New Name',
        ]);

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => 'New Name',
    ]);
});

it('fails updating another user with only own permission', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $otherUser = User::factory()->create();

    $user->givePermissionTo('USERS_UPDATE_OWN');

    Sanctum::actingAs($user);

    $response = $this->patchJson("/api/v1/users/{$otherUser->id}", [
        'name' => 'Hacked',
    ]);

    $response->assertStatus(403);
});

it('fails update validation when name is empty', function () {

    $admin = User::factory()->create([
        'locked' => false,
    ]);

    $admin->givePermissionTo('USERS_UPDATE_ANY');

    $user = User::factory()->create();

    Sanctum::actingAs($admin);

    $response = $this->patchJson("/api/v1/users/{$user->id}", [
        'name' => '',
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

it('fails update validation when password is too short', function () {

    $admin = User::factory()->create([
        'locked' => false,
    ]);

    $admin->givePermissionTo('USERS_UPDATE_ANY');

    $user = User::factory()->create();

    Sanctum::actingAs($admin);

    $response = $this->patchJson("/api/v1/users/{$user->id}", [
        'password' => '123',
        'password_confirmation' => '123',
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

it('fails update validation when password confirmation does not match', function () {

    $admin = User::factory()->create([
        'locked' => false,
    ]);

    $admin->givePermissionTo('USERS_UPDATE_ANY');

    $user = User::factory()->create();

    Sanctum::actingAs($admin);

    $response = $this->patchJson("/api/v1/users/{$user->id}", [
        'password' => 'password',
        'password_confirmation' => 'wrongpassword',
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

it('fails update validation when email already exists', function () {

    $admin = User::factory()->create([
        'locked' => false,
    ]);

    $admin->givePermissionTo('USERS_UPDATE_ANY');

    $user1 = User::factory()->create([
        'email' => 'first@example.com',
    ]);

    $user2 = User::factory()->create([
        'email' => 'second@example.com',
    ]);

    Sanctum::actingAs($admin);

    $response = $this->patchJson("/api/v1/users/{$user2->id}", [
        'email' => 'first@example.com',
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

it('fails update validation with invalid mobile number', function () {

    $admin = User::factory()->create([
        'locked' => false,
    ]);

    $admin->givePermissionTo('USERS_UPDATE_ANY');

    $user = User::factory()->create();

    Sanctum::actingAs($admin);

    $response = $this->patchJson("/api/v1/users/{$user->id}", [
        'mobile' => 'abc123',
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['mobile']);
});

it('fails update validation when locked is invalid', function () {

    $admin = User::factory()->create([
        'locked' => false,
    ]);

    $admin->givePermissionTo('USERS_UPDATE_ANY');

    $user = User::factory()->create();

    Sanctum::actingAs($admin);

    $response = $this->patchJson("/api/v1/users/{$user->id}", [
        'locked' => 'invalid',
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['locked']);
});

it('fails update validation when role does not exist', function () {

    $admin = User::factory()->create([
        'locked' => false,
    ]);

    $admin->givePermissionTo('USERS_UPDATE_ANY');

    $user = User::factory()->create();

    Sanctum::actingAs($admin);

    $response = $this->patchJson("/api/v1/users/{$user->id}", [
        'role' => 'fake-role',
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['role']);
});

it('deletes a user', function () {

    $admin = User::factory()->create([
        'locked' => false,
    ]);

    $admin->givePermissionTo('USERS_DELETE');

    $user = User::factory()->create();

    Sanctum::actingAs($admin);

    $response = $this->deleteJson("/api/v1/users/{$user->id}");

    $response->assertStatus(200);

    $this->assertSoftDeleted('users', [
        'id' => $user->id,
    ]);
});

it('fails deleting user without permission', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $targetUser = User::factory()->create();

    Sanctum::actingAs($user);

    $response = $this->deleteJson("/api/v1/users/{$targetUser->id}");

    $response->assertStatus(403);
});
