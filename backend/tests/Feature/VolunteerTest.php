<?php

use App\Models\User;
use App\Models\Volunteer;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
});

it('lists volunteers', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('VOLUNTEERS_LIST');

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/v1/volunteers');

    $response
        ->assertStatus(200)
        ->assertJsonStructure([
            'data',
            'links',
            'meta',
        ]);
});

it('fails listing volunteers without permission', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/v1/volunteers');

    $response->assertStatus(403);
});

it('creates a volunteer', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('VOLUNTEERS_CREATE');

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/volunteers', [
        'name' => 'Volunteer Test',
        'contact' => '912345678',
        'email' => 'volunteer@test.com',
        'team_identification' => 'Team A',
        'num_elements' => 5,
        'mission' => 'Support',
        'classification' => 'org',
        'start_datetime' => now()->toDateTimeString(),
        'end_datetime' => now()->addDay()->toDateTimeString(),
        'has_accommodation' => true,
        'location' => 'Porto',
        'has_meal' => true,
        'meal_notes' => 'Vegetarian',
        'meal_location' => 'Main Tent',
    ]);

    $response->assertStatus(201);

    $this->assertDatabaseHas('volunteers', [
        'email' => 'volunteer@test.com',
    ]);
});

it('fails creating volunteer without permission', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/volunteers', [
        'name' => 'Volunteer Test',
        'contact' => '912345678',
        'email' => 'volunteer@test.com',
        'classification' => 'single',
        'start_datetime' => now()->toDateTimeString(),
        'has_accommodation' => false,
        'has_meal' => false,
    ]);

    $response->assertStatus(403);
});

it('fails validation when name is missing', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('VOLUNTEERS_CREATE');

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/volunteers', [
        'name' => '',
        'contact' => '912345678',
        'email' => 'volunteer@test.com',
        'classification' => 'single',
        'start_datetime' => now()->toDateTimeString(),
        'has_accommodation' => false,
        'has_meal' => false,
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

it('fails validation with invalid email', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('VOLUNTEERS_CREATE');

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/volunteers', [
        'name' => 'Volunteer',
        'contact' => '912345678',
        'email' => 'email',
        'classification' => 'single',
        'start_datetime' => now()->toDateTimeString(),
        'has_accommodation' => false,
        'has_meal' => false,
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

it('fails validation with invalid contact', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('VOLUNTEERS_CREATE');

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/volunteers', [
        'name' => 'Volunteer',
        'contact' => 'abc123',
        'email' => 'volunteer@test.com',
        'classification' => 'single',
        'start_datetime' => now()->toDateTimeString(),
        'has_accommodation' => false,
        'has_meal' => false,
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['contact']);
});

it('fails validation when classification is invalid', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('VOLUNTEERS_CREATE');

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/volunteers', [
        'name' => 'Volunteer',
        'contact' => '912345678',
        'email' => 'volunteer@test.com',
        'classification' => 'invalid',
        'start_datetime' => now()->toDateTimeString(),
        'has_accommodation' => false,
        'has_meal' => false,
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['classification']);
});

it('fails validation when start_datetime is missing', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('VOLUNTEERS_CREATE');

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/volunteers', [
        'name' => 'Volunteer',
        'contact' => '912345678',
        'email' => 'volunteer@test.com',
        'classification' => 'single',
        'has_accommodation' => false,
        'has_meal' => false,
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['start_datetime']);
});

it('fails validation with invalid start_datetime', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('VOLUNTEERS_CREATE');

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/volunteers', [
        'name' => 'Volunteer',
        'contact' => '912345678',
        'email' => 'volunteer@test.com',
        'classification' => 'single',
        'start_datetime' => 'invalid-date',
        'has_accommodation' => false,
        'has_meal' => false,
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['start_datetime']);
});

it('fails validation when has_accommodation is invalid', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('VOLUNTEERS_CREATE');

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/volunteers', [
        'name' => 'Volunteer',
        'contact' => '912345678',
        'email' => 'volunteer@test.com',
        'classification' => 'single',
        'start_datetime' => now()->toDateTimeString(),
        'has_accommodation' => 'invalid',
        'has_meal' => false,
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['has_accommodation']);
});

it('fails validation when has_meal is invalid', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('VOLUNTEERS_CREATE');

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/volunteers', [
        'name' => 'Volunteer',
        'contact' => '912345678',
        'email' => 'volunteer@test.com',
        'classification' => 'single',
        'start_datetime' => now()->toDateTimeString(),
        'has_accommodation' => false,
        'has_meal' => 'invalid',
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['has_meal']);
});

it('fails validation when num_elements is invalid', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('VOLUNTEERS_CREATE');

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/volunteers', [
        'name' => 'Volunteer',
        'contact' => '912345678',
        'email' => 'volunteer@test.com',
        'classification' => 'org',
        'num_elements' => 'invalid',
        'start_datetime' => now()->toDateTimeString(),
        'has_accommodation' => false,
        'has_meal' => false,
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['num_elements']);
});

it('fails validation when num_elements is less than 1', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('VOLUNTEERS_CREATE');

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/volunteers', [
        'name' => 'Volunteer',
        'contact' => '912345678',
        'email' => 'volunteer@test.com',
        'classification' => 'org',
        'num_elements' => 0,
        'start_datetime' => now()->toDateTimeString(),
        'has_accommodation' => false,
        'has_meal' => false,
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['num_elements']);
});

it('shows a volunteer', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('VOLUNTEERS_LIST');

    Sanctum::actingAs($user);

    $volunteer = Volunteer::factory()->create();

    $response = $this->getJson("/api/v1/volunteers/{$volunteer->id}");

    $response
        ->assertStatus(200)
        ->assertJsonFragment([
            'email' => $volunteer->email,
        ]);
});

it('fails showing volunteer without permission', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $volunteer = Volunteer::factory()->create();

    Sanctum::actingAs($user);

    $response = $this->getJson("/api/v1/volunteers/{$volunteer->id}");

    $response->assertStatus(403);
});

it('updates a volunteer', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('VOLUNTEERS_UPDATE');

    Sanctum::actingAs($user);

    $volunteer = Volunteer::factory()->create();

    $response = $this->patchJson("/api/v1/volunteers/{$volunteer->id}", [
        'name' => 'Updated Volunteer',
    ]);

    $response->assertStatus(200);

    $this->assertDatabaseHas('volunteers', [
        'id' => $volunteer->id,
        'name' => 'Updated Volunteer',
    ]);
});

it('fails updating volunteer without permission', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $volunteer = Volunteer::factory()->create();

    Sanctum::actingAs($user);

    $response = $this->patchJson("/api/v1/volunteers/{$volunteer->id}", [
        'name' => 'Updated Name',
    ]);

    $response->assertStatus(403);
});

it('fails update validation with invalid email', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('VOLUNTEERS_UPDATE');

    Sanctum::actingAs($user);

    $volunteer = Volunteer::factory()->create();

    $response = $this->patchJson("/api/v1/volunteers/{$volunteer->id}", [
        'email' => 'invalid-email',
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

it('fails update validation with invalid contact', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('VOLUNTEERS_UPDATE');

    Sanctum::actingAs($user);

    $volunteer = Volunteer::factory()->create();

    $response = $this->patchJson("/api/v1/volunteers/{$volunteer->id}", [
        'contact' => 'abc123',
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['contact']);
});

it('fails update validation when classification is invalid', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('VOLUNTEERS_UPDATE');

    Sanctum::actingAs($user);

    $volunteer = Volunteer::factory()->create();

    $response = $this->patchJson("/api/v1/volunteers/{$volunteer->id}", [
        'classification' => 'invalid',
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['classification']);
});

it('fails update validation when start_datetime is invalid', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('VOLUNTEERS_UPDATE');

    Sanctum::actingAs($user);

    $volunteer = Volunteer::factory()->create();

    $response = $this->patchJson("/api/v1/volunteers/{$volunteer->id}", [
        'start_datetime' => 'invalid-date',
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['start_datetime']);
});

it('fails update validation when has_accommodation is invalid', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('VOLUNTEERS_UPDATE');

    Sanctum::actingAs($user);

    $volunteer = Volunteer::factory()->create();

    $response = $this->patchJson("/api/v1/volunteers/{$volunteer->id}", [
        'has_accommodation' => 'invalid',
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['has_accommodation']);
});

it('fails update validation when has_meal is invalid', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('VOLUNTEERS_UPDATE');

    Sanctum::actingAs($user);

    $volunteer = Volunteer::factory()->create();

    $response = $this->patchJson("/api/v1/volunteers/{$volunteer->id}", [
        'has_meal' => 'invalid',
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['has_meal']);
});

it('fails update validation when num_elements is invalid', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('VOLUNTEERS_UPDATE');

    Sanctum::actingAs($user);

    $volunteer = Volunteer::factory()->create();

    $response = $this->patchJson("/api/v1/volunteers/{$volunteer->id}", [
        'num_elements' => 'invalid',
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['num_elements']);
});

it('fails update validation when num_elements is less than 1', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('VOLUNTEERS_UPDATE');

    Sanctum::actingAs($user);

    $volunteer = Volunteer::factory()->create();

    $response = $this->patchJson("/api/v1/volunteers/{$volunteer->id}", [
        'num_elements' => 0,
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['num_elements']);
});

it('fails update validation when end_datetime is invalid', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('VOLUNTEERS_UPDATE');

    Sanctum::actingAs($user);

    $volunteer = Volunteer::factory()->create();

    $response = $this->patchJson("/api/v1/volunteers/{$volunteer->id}", [
        'end_datetime' => 'invalid-date',
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['end_datetime']);
});

it('sets num_elements to 1 when classification is single', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('VOLUNTEERS_CREATE');

    Sanctum::actingAs($user);

    $this->postJson('/api/v1/volunteers', [
        'name' => 'Volunteer',
        'contact' => '912345678',
        'email' => 'volunteer@test.com',
        'classification' => 'single',
        'num_elements' => 10,
        'start_datetime' => now()->toDateTimeString(),
        'has_accommodation' => false,
        'has_meal' => false,
    ]);

    $this->assertDatabaseHas('volunteers', [
        'email' => 'volunteer@test.com',
        'num_elements' => 1,
    ]);
});

it('sets location to null when has_accommodation is false', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('VOLUNTEERS_CREATE');

    Sanctum::actingAs($user);

    $this->postJson('/api/v1/volunteers', [
        'name' => 'Volunteer',
        'contact' => '912345678',
        'email' => 'volunteer@test.com',
        'classification' => 'single',
        'start_datetime' => now()->toDateTimeString(),
        'has_accommodation' => false,
        'location' => 'Porto',
        'has_meal' => false,
    ]);

    $this->assertDatabaseHas('volunteers', [
        'email' => 'volunteer@test.com',
        'location' => null,
    ]);
});

it('sets meal fields to null when has_meal is false', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('VOLUNTEERS_CREATE');

    Sanctum::actingAs($user);

    $this->postJson('/api/v1/volunteers', [
        'name' => 'Volunteer',
        'contact' => '912345678',
        'email' => 'volunteer@test.com',
        'classification' => 'single',
        'start_datetime' => now()->toDateTimeString(),
        'has_accommodation' => false,
        'has_meal' => false,
        'meal_notes' => 'Vegetarian',
        'meal_location' => 'Main Tent',
    ]);

    $this->assertDatabaseHas('volunteers', [
        'email' => 'volunteer@test.com',
        'meal_notes' => null,
        'meal_location' => null,
    ]);
});

it('deletes a volunteer', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('VOLUNTEERS_DELETE');

    Sanctum::actingAs($user);

    $volunteer = Volunteer::factory()->create();

    $response = $this->deleteJson("/api/v1/volunteers/{$volunteer->id}");

    $response->assertStatus(200);

    $this->assertSoftDeleted('volunteers', [
        'id' => $volunteer->id,
    ]);
});

it('fails deleting volunteer without permission', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $volunteer = Volunteer::factory()->create();

    Sanctum::actingAs($user);

    $response = $this->deleteJson("/api/v1/volunteers/{$volunteer->id}");

    $response->assertStatus(403);
});
