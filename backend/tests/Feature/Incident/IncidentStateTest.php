<?php

use App\Models\User;
use App\Models\IncidentState;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
});

it('lists incident states', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('INCIDENT_STATES_LIST');

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/v1/incidentStates');

    $response
        ->assertStatus(200)
        ->assertJsonStructure(['data', 'links', 'meta']);
});

it('fails listing incident states without permission', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/v1/incidentStates');

    $response->assertStatus(403);
});

it('filters incident states by search', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('INCIDENT_STATES_LIST');

    Sanctum::actingAs($user);

    IncidentState::factory()->create([
        'name' => 'Resolved',
    ]);

    IncidentState::factory()->create([
        'name' => 'Pending',
    ]);

    $response = $this->getJson('/api/v1/incidentStates?filter[search]=Res');

    $response
        ->assertStatus(200)
        ->assertJsonFragment(['name' => 'Resolved'])
        ->assertJsonMissing(['name' => 'Pending']);
});

it('filters incident states by active status', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('INCIDENT_STATES_LIST');

    Sanctum::actingAs($user);

    IncidentState::factory()->create([
        'name' => 'Active State',
        'is_active' => true,
    ]);

    IncidentState::factory()->create([
        'name' => 'Inactive State',
        'is_active' => false,
    ]);

    $response = $this->getJson('/api/v1/incidentStates?filter[status]=1&per_page=50');

    $response
        ->assertStatus(200)
        ->assertJsonFragment(['name' => 'Active State'])
        ->assertJsonMissing(['name' => 'Inactive State']);
});

it('filters incident states by terminates incident', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('INCIDENT_STATES_LIST');

    Sanctum::actingAs($user);

    IncidentState::factory()->create([
        'name' => 'Final State',
        'terminates_incident' => true,
    ]);

    IncidentState::factory()->create([
        'name' => 'Ongoing State',
        'terminates_incident' => false,
    ]);

    $response = $this->getJson('/api/v1/incidentStates?filter[terminates]=1');

    $response
        ->assertStatus(200)
        ->assertJsonFragment(['name' => 'Final State'])
        ->assertJsonMissing(['name' => 'Ongoing State']);
});

it('creates an incident state', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('INCIDENT_STATES_CREATE');

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/incidentStates', ['name' => 'In Progress', 'description' => 'Incident is ongoing', 'hex_color' => '#FFAA00', 'terminates_incident' => false, 'is_active' => true]);

    $response
        ->assertStatus(201)
        ->assertJsonFragment(['name' => 'In Progress']);

    $this->assertDatabaseHas('incident_states', ['name' => 'In Progress']);
});

it('fails creating incident state without permission', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/incidentStates', ['name' => 'Blocked', 'hex_color' => '#FFFFFF', 'terminates_incident' => false, 'is_active' => true]);

    $response->assertStatus(403);
});

it('fails validation when name is missing on create', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('INCIDENT_STATES_CREATE');

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/incidentStates', ['description' => 'No name', 'hex_color' => '#FFFFFF', 'terminates_incident' => false, 'is_active' => true]);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

it('fails validation when hex color is invalid', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('INCIDENT_STATES_CREATE');

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/incidentStates', ['name' => 'Invalid Color', 'hex_color' => 'red', 'terminates_incident' => false, 'is_active' => true]);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['hex_color']);
});

it('fails validation when terminates incident is invalid', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('INCIDENT_STATES_CREATE');

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/incidentStates', ['name' => 'Invalid State', 'hex_color' => '#FFFFFF', 'terminates_incident' => 'invalid', 'is_active' => true]);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['terminates_incident',]);
});

it('fails validation when is active is invalid', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('INCIDENT_STATES_CREATE');

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/incidentStates', ['name' => 'Invalid Active', 'hex_color' => '#FFFFFF', 'terminates_incident' => false, 'is_active' => 'invalid']);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['is_active']);
});

it('shows an incident state', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('INCIDENT_STATES_LIST');

    Sanctum::actingAs($user);

    $incidentState = IncidentState::factory()->create([
        'name' => 'Open',
    ]);

    $response = $this->getJson("/api/v1/incidentStates/{$incidentState->id}");

    $response
        ->assertStatus(200)
        ->assertJsonFragment(['name' => 'Open']);
});

it('fails showing incident state without permission', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    Sanctum::actingAs($user);

    $incidentState = IncidentState::factory()->create();

    $response = $this->getJson("/api/v1/incidentStates/{$incidentState->id}");

    $response->assertStatus(403);
});

it('updates an incident state', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('INCIDENT_STATES_UPDATE');

    Sanctum::actingAs($user);

    $incidentState = IncidentState::factory()->create([
        'name' => 'Old State'
    ]);

    $response = $this->patchJson("/api/v1/incidentStates/{$incidentState->id}", ['name' => 'Updated State']);

    $response
        ->assertStatus(200)
        ->assertJsonFragment(['name' => 'Updated State']);

    $this->assertDatabaseHas('incident_states', ['id' => $incidentState->id, 'name' => 'Updated State']);
});

it('fails updating incident state without permission', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    Sanctum::actingAs($user);

    $incidentState = IncidentState::factory()->create();

    $response = $this->patchJson("/api/v1/incidentStates/{$incidentState->id}", ['name' => 'Unauthorized Update',]);

    $response->assertStatus(403);
});

it('fails update validation when hex color is invalid', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('INCIDENT_STATES_UPDATE');

    Sanctum::actingAs($user);

    $incidentState = IncidentState::factory()->create();

    $response = $this->patchJson("/api/v1/incidentStates/{$incidentState->id}", ['hex_color' => 'blue']);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['hex_color']);
});

it('fails update validation when terminates incident is invalid', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('INCIDENT_STATES_UPDATE');

    Sanctum::actingAs($user);

    $incidentState = IncidentState::factory()->create();

    $response = $this->patchJson("/api/v1/incidentStates/{$incidentState->id}", ['terminates_incident' => 'invalid',]);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['terminates_incident',]);
});

it('fails update validation when is active is invalid', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('INCIDENT_STATES_UPDATE');

    Sanctum::actingAs($user);

    $incidentState = IncidentState::factory()->create();

    $response = $this->patchJson("/api/v1/incidentStates/{$incidentState->id}", ['is_active' => 'invalid']);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['is_active']);
});

it('deletes an incident state', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->givePermissionTo('INCIDENT_STATES_DELETE');

    Sanctum::actingAs($user);

    $incidentState = IncidentState::factory()->create();

    $response = $this->deleteJson("/api/v1/incidentStates/{$incidentState->id}");

    $response->assertStatus(200);

    $this->assertSoftDeleted('incident_states', ['id' => $incidentState->id,]);
});

it('fails deleting incident state without permission', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    Sanctum::actingAs($user);

    $incidentState = IncidentState::factory()->create();

    $response = $this->deleteJson("/api/v1/incidentStates/{$incidentState->id}");

    $response->assertStatus(403);
});
