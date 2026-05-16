<?php

use App\Models\User;
use App\Models\EntityType;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
});

it('lists entity types', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('ENTITY_TYPES_LIST');

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/v1/entityTypes');

    $response
        ->assertStatus(200)
        ->assertJsonStructure(['data', 'links', 'meta',]);
});

it('fails listing entity types without permission', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/v1/entityTypes');

    $response->assertStatus(403);
});

it('filters entity types by search', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('ENTITY_TYPES_LIST');

    Sanctum::actingAs($user);

    EntityType::factory()->create([
        'name' => 'Hospital',
    ]);

    EntityType::factory()->create([
        'name' => 'Fire Department',
    ]);

    $response = $this->getJson('/api/v1/entityTypes?filter[search]=Hosp');

    $response
        ->assertStatus(200)
        ->assertJsonFragment(['name' => 'Hospital',])
        ->assertJsonMissing(['name' => 'Fire Department',]);
});

it('creates an entity type', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('ENTITY_TYPES_CREATE');

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/entityTypes', [
        'name' => 'Medical Unit',
        'description' => 'Medical support entities',
    ]);

    $response
        ->assertStatus(201)
        ->assertJsonFragment(['name' => 'Medical Unit',]);

    $this->assertDatabaseHas('entity_types', [
        'name' => 'Medical Unit',
    ]);
});

it('fails creating entity type without permission', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/entityTypes', [
        'name' => 'Restricted Type',
    ]);

    $response->assertStatus(403);
});

it('fails validation when name is missing on create', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('ENTITY_TYPES_CREATE');

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/entityTypes', [
        'description' => 'No name',
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

it('shows an entity type', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('ENTITY_TYPES_LIST');

    Sanctum::actingAs($user);

    $entityType = EntityType::factory()->create([
        'name' => 'Police',
    ]);

    $response = $this->getJson("/api/v1/entityTypes/{$entityType->id}");

    $response
        ->assertStatus(200)
        ->assertJsonFragment(['name' => 'Police',]);
});

it('fails showing entity type without permission', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    Sanctum::actingAs($user);

    $entityType = EntityType::factory()->create();

    $response = $this->getJson("/api/v1/entityTypes/{$entityType->id}");

    $response->assertStatus(403);
});

it('updates an entity type', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('ENTITY_TYPES_UPDATE');

    Sanctum::actingAs($user);

    $entityType = EntityType::factory()->create([
        'name' => 'Old Name',
    ]);

    $response = $this->patchJson("/api/v1/entityTypes/{$entityType->id}", [
        'name' => 'Updated Name',
    ]);

    $response
        ->assertStatus(200)
        ->assertJsonFragment(['name' => 'Updated Name',]);

    $this->assertDatabaseHas('entity_types', [
        'id' => $entityType->id,
        'name' => 'Updated Name',
    ]);
});

it('fails updating entity type without permission', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    Sanctum::actingAs($user);

    $entityType = EntityType::factory()->create();

    $response = $this->patchJson("/api/v1/entityTypes/{$entityType->id}", [
        'name' => 'Updated Type',
    ]);

    $response->assertStatus(403);
});

it('fails update validation when name is empty', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('ENTITY_TYPES_UPDATE');

    Sanctum::actingAs($user);

    $entityType = EntityType::factory()->create();

    $response = $this->patchJson("/api/v1/entityTypes/{$entityType->id}", [
        'name' => '',
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

it('deletes an entity type', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('ENTITY_TYPES_DELETE');

    Sanctum::actingAs($user);

    $entityType = EntityType::factory()->create();

    $response = $this->deleteJson("/api/v1/entityTypes/{$entityType->id}");

    $response->assertStatus(200);

    $this->assertSoftDeleted('entity_types', [
        'id' => $entityType->id,
    ]);
});

it('fails deleting entity type without permission', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    Sanctum::actingAs($user);

    $entityType = EntityType::factory()->create();

    $response = $this->deleteJson("/api/v1/entityTypes/{$entityType->id}");

    $response->assertStatus(403);
});
