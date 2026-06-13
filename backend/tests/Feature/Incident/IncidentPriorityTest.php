<?php

use App\Models\User;
use App\Models\IncidentPriority;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
});

it('lists incident priorities', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('INCIDENT_PRIORITIES_LIST');

    Sanctum::actingAs($user);

    IncidentPriority::factory()->count(3)->create();

    $response = $this->getJson('/api/v1/incidentPriorities');

    $response
        ->assertStatus(200)
        ->assertJsonStructure(['data', 'links', 'meta',]);
});

it('fails listing incident priorities without permission', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/v1/incidentPriorities');

    $response->assertStatus(403);
});

it('filters incident priorities by search', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('INCIDENT_PRIORITIES_LIST');

    Sanctum::actingAs($user);

    IncidentPriority::factory()->create([
        'name' => 'High',
        'description' => 'Critical incidents',
    ]);

    IncidentPriority::factory()->create([
        'name' => 'Low',
        'description' => 'Minor incidents',
    ]);

    $response = $this->getJson('/api/v1/incidentPriorities?filter[search]=Critical');

    $response
        ->assertStatus(200)
        ->assertJsonFragment(['name' => 'High',])
        ->assertJsonMissing(['name' => 'Low',]);
});

it('filters incident priorities by status', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('INCIDENT_PRIORITIES_LIST');

    Sanctum::actingAs($user);

    IncidentPriority::factory()->create([
        'name' => 'Active Priority',
        'is_active' => true,
    ]);

    IncidentPriority::factory()->create([
        'name' => 'Inactive Priority',
        'is_active' => false,
    ]);

    $response = $this->getJson('/api/v1/incidentPriorities?filter[status]=1');

    $response
        ->assertStatus(200)
        ->assertJsonFragment(['name' => 'Active Priority',])
        ->assertJsonMissing(['name' => 'Inactive Priority',]);
});

it('shows an incident priority', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('INCIDENT_PRIORITIES_LIST');

    Sanctum::actingAs($user);

    $priority = IncidentPriority::factory()->create([
        'name' => 'Critical',
    ]);

    $response = $this->getJson("/api/v1/incidentPriorities/{$priority->id}");

    $response
        ->assertStatus(200)
        ->assertJsonFragment(['name' => 'Critical',]);
});

it('fails showing incident priority without permission', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $priority = IncidentPriority::factory()->create();

    Sanctum::actingAs($user);

    $response = $this->getJson("/api/v1/incidentPriorities/{$priority->id}");

    $response->assertStatus(403);
});

it('creates an incident priority', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('INCIDENT_PRIORITIES_CREATE');

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/incidentPriorities', ['name' => 'Urgent', 'description' => 'Urgent incidents', 'hex_color' => '#FF0000', 'is_active' => true,]);

    $response
        ->assertStatus(201)
        ->assertJsonFragment(['name' => 'Urgent',]);

    $this->assertDatabaseHas('incident_priorities', ['name' => 'Urgent',]);
});

it('fails creating incident priority without permission', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/incidentPriorities', ['name' => 'Restricted', 'description' => 'Restricted', 'hex_color' => '#000000', 'is_active' => true,]);

    $response->assertStatus(403);
});

it('fails validation when name is missing', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('INCIDENT_PRIORITIES_CREATE');

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/incidentPriorities', ['description' => 'Missing name', 'hex_color' => '#FFFFFF', 'is_active' => true,]);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

it('fails validation when description is missing', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('INCIDENT_PRIORITIES_CREATE');

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/incidentPriorities', ['name' => 'Priority', 'hex_color' => '#FFFFFF', 'is_active' => true,]);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['description']);
});

it('fails validation when hex color is missing', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('INCIDENT_PRIORITIES_CREATE');

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/incidentPriorities', ['name' => 'Priority', 'description' => 'Description', 'is_active' => true,]);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['hex_color']);
});

it('fails validation when hex color is invalid', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('INCIDENT_PRIORITIES_CREATE');

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/incidentPriorities', [ 'name' => 'Priority', 'description' => 'Description', 'hex_color' => '#ZZZZZZ', 'is_active' => true]);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['hex_color']);
});

it('fails validation when is active is invalid', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('INCIDENT_PRIORITIES_CREATE');

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/incidentPriorities', ['name' => 'Priority', 'description' => 'Description', 'hex_color' => '#FFFFFF', 'is_active' => 'invalid',]);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['is_active']);
});

it('fails validation when incident priority name already exists', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('INCIDENT_PRIORITIES_CREATE');

    Sanctum::actingAs($user);

    IncidentPriority::factory()->create([
        'name' => 'Critical',
    ]);

    $response = $this->postJson('/api/v1/incidentPriorities', ['name' => 'Critical', 'description' => 'Duplicate', 'hex_color' => '#FFFFFF', 'is_active' => true,]);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

it('updates an incident priority', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('INCIDENT_PRIORITIES_UPDATE');

    Sanctum::actingAs($user);

    $priority = IncidentPriority::factory()->create([
        'name' => 'Old Priority',
    ]);

    $response = $this->patchJson("/api/v1/incidentPriorities/{$priority->id}", ['name' => 'Updated Priority']);

    $response
        ->assertStatus(200)
        ->assertJsonFragment(['name' => 'Updated Priority']);

    $this->assertDatabaseHas('incident_priorities', ['id' => $priority->id, 'name' => 'Updated Priority']);
});

it('fails updating incident priority without permission', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $priority = IncidentPriority::factory()->create();

    Sanctum::actingAs($user);

    $response = $this->patchJson("/api/v1/incidentPriorities/{$priority->id}", ['name' => 'Hacked',]);

    $response->assertStatus(403);
});

it('fails update validation when name already exists', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('INCIDENT_PRIORITIES_UPDATE');

    Sanctum::actingAs($user);

    IncidentPriority::factory()->create([
        'name' => 'Critical',
    ]);

    $priority = IncidentPriority::factory()->create([
        'name' => 'Low',
    ]);

    $response = $this->patchJson("/api/v1/incidentPriorities/{$priority->id}", ['name' => 'Critical',]);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

it('fails update validation when hex color is invalid', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('INCIDENT_PRIORITIES_UPDATE');

    Sanctum::actingAs($user);

    $priority = IncidentPriority::factory()->create();

    $response = $this->patchJson("/api/v1/incidentPriorities/{$priority->id}", ['hex_color' => 'red',]);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['hex_color']);
});

it('deletes an incident priority', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('INCIDENT_PRIORITIES_DELETE');

    Sanctum::actingAs($user);

    $priority = IncidentPriority::factory()->create();

    $response = $this->deleteJson("/api/v1/incidentPriorities/{$priority->id}");

    $response->assertStatus(200);

    $this->assertSoftDeleted('incident_priorities', ['id' => $priority->id,]);
});

it('fails deleting incident priority without permission', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $priority = IncidentPriority::factory()->create();

    Sanctum::actingAs($user);

    $response = $this->deleteJson("/api/v1/incidentPriorities/{$priority->id}");

    $response->assertStatus(403);
});
