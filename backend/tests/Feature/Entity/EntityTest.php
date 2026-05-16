<?php

use App\Models\User;
use App\Models\Entity;
use App\Models\EntityType;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
});

it('lists entities', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('ENTITIES_LIST');

    Sanctum::actingAs($user);

    Entity::factory()->count(3)->create();

    $response = $this->getJson('/api/v1/entities');

    $response
        ->assertStatus(200)
        ->assertJsonStructure(['data', 'links', 'meta',]);
});

it('fails listing entities without permission', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/v1/entities');

    $response->assertStatus(403);
});

it('filters entities by search', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('ENTITIES_LIST');

    Sanctum::actingAs($user);

    Entity::factory()->create([
        'name' => 'Hospital Central',
    ]);

    Entity::factory()->create([
        'name' => 'Fire Station',
    ]);

    $response = $this->getJson('/api/v1/entities?filter[search]=Hosp');

    $response
        ->assertStatus(200)
        ->assertJsonFragment(['name' => 'Hospital Central',])
        ->assertJsonMissing(['name' => 'Fire Station',]);
});

it('filters entities by type', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('ENTITIES_LIST');

    Sanctum::actingAs($user);

    $hospitalType = EntityType::factory()->create();
    $fireType = EntityType::factory()->create();

    Entity::factory()->create([
        'entity_type_id' => $hospitalType->id,
        'name' => 'Hospital',
    ]);

    Entity::factory()->create([
        'entity_type_id' => $fireType->id,
        'name' => 'Fire Department',
    ]);

    $response = $this->getJson("/api/v1/entities?filter[type]={$hospitalType->id}");

    $response
        ->assertStatus(200)
        ->assertJsonFragment(['name' => 'Hospital',])
        ->assertJsonMissing(['name' => 'Fire Department',]);
});

it('shows an entity', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('ENTITIES_LIST');

    Sanctum::actingAs($user);

    $entity = Entity::factory()->create([
        'name' => 'Medical Team',
    ]);

    $response = $this->getJson("/api/v1/entities/{$entity->id}");

    $response
        ->assertStatus(200)
        ->assertJsonFragment(['name' => 'Medical Team',]);
});

it('fails showing entity without permission', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $entity = Entity::factory()->create();

    Sanctum::actingAs($user);

    $response = $this->getJson("/api/v1/entities/{$entity->id}");

    $response->assertStatus(403);
});

it('creates an entity', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $type = EntityType::factory()->create();

    $user->givePermissionTo('ENTITIES_CREATE');

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/entities', [
        'name' => 'Rescue Team',
        'description' => 'Emergency rescue unit',
        'phone_contact' => '+351 912345678',
        'email_contact' => 'entity@example.com',
        'address' => 'Main Street',
        'poc_name' => 'John Doe',
        'poc_phone' => '+351 923456789',
        'poc_email' => 'john@example.com',
        'entity_type_id' => $type->id,
    ]);

    $response
        ->assertStatus(201)
        ->assertJsonFragment(['name' => 'Rescue Team',]);

    $this->assertDatabaseHas('entities', ['name' => 'Rescue Team',]);
});

it('fails creating entity without permission', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/entities', ['name' => 'Restricted Entity',]);

    $response->assertStatus(403);
});

it('fails validation when name is missing', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('ENTITIES_CREATE');

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/entities', ['description' => 'Missing name',]);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

it('fails validation with invalid entity email', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('ENTITIES_CREATE');

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/entities', ['name' => 'Entity', 'email_contact' => 'invalid-email',]);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['email_contact']);
});

it('fails validation with invalid poc email', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('ENTITIES_CREATE');

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/entities', ['name' => 'Entity', 'poc_email' => 'invalid-email',]);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['poc_email']);
});

it('fails validation with invalid entity phone', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('ENTITIES_CREATE');

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/entities', ['name' => 'Entity', 'phone_contact' => 'abc123',]);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['phone_contact']);
});

it('fails validation with invalid poc phone', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('ENTITIES_CREATE');

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/entities', ['name' => 'Entity', 'poc_phone' => 'abc123',]);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['poc_phone']);
});

it('fails validation when entity type does not exist', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('ENTITIES_CREATE');

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/entities', ['name' => 'Entity', 'entity_type_id' => 999999,]);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['entity_type_id']);
});

it('updates an entity', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('ENTITIES_UPDATE');

    Sanctum::actingAs($user);

    $entity = Entity::factory()->create([
        'name' => 'Old Entity',
    ]);

    $response = $this->patchJson("/api/v1/entities/{$entity->id}", ['name' => 'Updated Entity',]);

    $response
        ->assertStatus(200)
        ->assertJsonFragment(['name' => 'Updated Entity',]);

    $this->assertDatabaseHas('entities', [
        'id' => $entity->id,
        'name' => 'Updated Entity',
    ]);
});

it('fails updating entity without permission', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $entity = Entity::factory()->create();

    Sanctum::actingAs($user);

    $response = $this->patchJson("/api/v1/entities/{$entity->id}", [
        'name' => 'Hacked Entity',
    ]);

    $response->assertStatus(403);
});

it('fails update validation when email is invalid', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('ENTITIES_UPDATE');

    Sanctum::actingAs($user);

    $entity = Entity::factory()->create();

    $response = $this->patchJson("/api/v1/entities/{$entity->id}", [
        'email_contact' => 'invalid-email',
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['email_contact']);
});

it('fails update validation when phone is invalid', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('ENTITIES_UPDATE');

    Sanctum::actingAs($user);

    $entity = Entity::factory()->create();

    $response = $this->patchJson("/api/v1/entities/{$entity->id}", [
        'phone_contact' => 'abc123',
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['phone_contact']);
});

it('deletes an entity', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('ENTITIES_DELETE');

    Sanctum::actingAs($user);

    $entity = Entity::factory()->create();

    $response = $this->deleteJson("/api/v1/entities/{$entity->id}");

    $response->assertStatus(200);

    $this->assertSoftDeleted('entities', ['id' => $entity->id,]);
});

it('fails deleting entity without permission', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $entity = Entity::factory()->create();

    Sanctum::actingAs($user);

    $response = $this->deleteJson("/api/v1/entities/{$entity->id}");

    $response->assertStatus(403);
});
