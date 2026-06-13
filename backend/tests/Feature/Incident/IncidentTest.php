<?php

use App\Models\Incident;
use App\Models\IncidentPriority;
use App\Models\IncidentState;
use App\Models\IncidentType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {

    $this->seed();

    $this->user = User::factory()->create([
        'locked' => false,
    ]);

    $this->payload = [
        'identifier' => 'INC-001',
        'incident_type_id' => IncidentType::query()->first()->id,
        'incident_state_id' => IncidentState::query()->first()->id,
        'incident_priority_id' => IncidentPriority::query()->first()->id,
        'start_datetime' => now()->toDateTimeString(),
        'end_datetime' => now()->addHour()->toDateTimeString(),
        'coordinates' => '39.123,-8.123',
        'common_place' => 'Centro',
        'address' => 'Rua Principal',
        'parish' => 'Freguesia',
        'municipality' => 'Município',
        'district' => 'Distrito',
        'is_major' => false,
        'alert_source_relationship' => 'Witness',
        'alert_source_name' => 'João',
        'alert_source_contact' => '912345678',
        'obs' => 'Observações',
        'incident_id' => null,
        'user_id' => $this->user->id,
        'coordinates_pco' => '39.123,-8.123',
        'name_pco' => 'PCO',
    ];
});

it('lists incidents', function () {
    $this->user->givePermissionTo('INCIDENTS_LIST');

    Sanctum::actingAs($this->user);

    Incident::factory()->count(3)->create();

    $response = $this->getJson('/api/v1/incidents');

    $response->assertStatus(200)->assertJsonStructure(['data', 'links', 'meta',]);
});

it('fails listing incidents without permission', function () {
    Sanctum::actingAs($this->user);

    $response = $this->getJson('/api/v1/incidents');

    $response->assertStatus(403);
});

it('filters incidents by search', function () {
    $this->user->givePermissionTo('INCIDENTS_LIST');

    Sanctum::actingAs($this->user);

    Incident::factory()->create(['identifier' => 'SPECIAL-INCIDENT']);

    Incident::factory()->create(['identifier' => 'OTHER']);

    $response = $this->getJson('/api/v1/incidents?filter[search]=SPECIAL');

    $response
        ->assertStatus(200)
        ->assertJsonFragment(['identifier' => 'SPECIAL-INCIDENT'])
        ->assertJsonMissing(['identifier' => 'OTHER']);
});

it('filters incidents by state', function () {
    $this->user->givePermissionTo('INCIDENTS_LIST');

    Sanctum::actingAs($this->user);

    $state = IncidentState::factory()->create();

    Incident::factory()->create(['identifier' => 'STATE-OK', 'incident_state_id' => $state->id]);

    Incident::factory()->create(['identifier' => 'STATE-NO']);

    $response = $this->getJson("/api/v1/incidents?filter[state]={$state->id}");

    $response
        ->assertStatus(200)
        ->assertJsonFragment(['identifier' => 'STATE-OK'])
        ->assertJsonMissing(['identifier' => 'STATE-NO']);
});

it('filters incidents by priority', function () {
    $this->user->givePermissionTo('INCIDENTS_LIST');

    Sanctum::actingAs($this->user);

    $priority = IncidentPriority::factory()->create();

    Incident::factory()->create(['identifier' => 'PRIORITY-OK', 'incident_priority_id' => $priority->id]);

    Incident::factory()->create(['identifier' => 'PRIORITY-NO']);

    $response = $this->getJson("/api/v1/incidents?filter[priority]={$priority->id}");

    $response
        ->assertStatus(200)
        ->assertJsonFragment(['identifier' => 'PRIORITY-OK'])
        ->assertJsonMissing(['identifier' => 'PRIORITY-NO']);
});

it('creates an incident', function () {
    $this->user->givePermissionTo('INCIDENTS_CREATE');

    Sanctum::actingAs($this->user);

    $response = $this->postJson('/api/v1/incidents', $this->payload);

    $response->assertStatus(201)->assertJsonFragment(['identifier' => 'INC-001']);

    $this->assertDatabaseHas('incidents', ['identifier' => 'INC-001']);
});

it('fails creating incident without permission', function () {
    Sanctum::actingAs($this->user);

    $response = $this->postJson('/api/v1/incidents', $this->payload);

    $response->assertStatus(403);
});

it('creates major incident with children', function () {
    $this->user->givePermissionTo('INCIDENTS_CREATE');

    Sanctum::actingAs($this->user);

    $child1 = Incident::factory()->create();
    $child2 = Incident::factory()->create();

    $payload = [
        ...$this->payload,
        'identifier' => 'MAJOR-001',
        'is_major' => true,
        'children_incidents' => [$child1->id, $child2->id]
    ];

    $response = $this->postJson('/api/v1/incidents', $payload);

    $response->assertStatus(201);

    $incidentId = $response->json('data.id');

    $this->assertDatabaseHas('incidents', [
        'id' => $child1->id,
        'incident_id' => $incidentId
    ]);

    $this->assertDatabaseHas('incidents', [
        'id' => $child2->id,
        'incident_id' => $incidentId
    ]);
});

it('fails validation when identifier is missing', function () {
    $this->user->givePermissionTo('INCIDENTS_CREATE');

    Sanctum::actingAs($this->user);

    $payload = $this->payload;

    unset($payload['identifier']);

    $response = $this->postJson('/api/v1/incidents', $payload);

    $response->assertStatus(422)->assertJsonValidationErrors(['identifier']);
});

it('fails validation when identifier is duplicated', function () {
    $this->user->givePermissionTo('INCIDENTS_CREATE');

    Sanctum::actingAs($this->user);

    Incident::factory()->create(['identifier' => 'DUPLICATED']);

    $payload = [
        ...$this->payload,
        'identifier' => 'DUPLICATED',
    ];

    $response = $this->postJson('/api/v1/incidents', $payload);

    $response->assertStatus(422)->assertJsonValidationErrors(['identifier']);
});

it('fails validation when incident type does not exist', function () {
    $this->user->givePermissionTo('INCIDENTS_CREATE');

    Sanctum::actingAs($this->user);

    $response = $this->postJson('/api/v1/incidents', [...$this->payload, 'incident_type_id' => 999999]);

    $response->assertStatus(422)->assertJsonValidationErrors(['incident_type_id']);
});

it('fails validation when incident state does not exist', function () {
    $this->user->givePermissionTo('INCIDENTS_CREATE');

    Sanctum::actingAs($this->user);

    $response = $this->postJson('/api/v1/incidents', [...$this->payload, 'incident_state_id' => 999999]);

    $response->assertStatus(422)->assertJsonValidationErrors(['incident_state_id']);
});

it('fails validation when incident priority does not exist', function () {
    $this->user->givePermissionTo('INCIDENTS_CREATE');

    Sanctum::actingAs($this->user);

    $response = $this->postJson('/api/v1/incidents', [...$this->payload, 'incident_priority_id' => 999999]);

    $response->assertStatus(422)->assertJsonValidationErrors(['incident_priority_id']);
});

it('fails validation when start datetime is invalid', function () {
    $this->user->givePermissionTo('INCIDENTS_CREATE');

    Sanctum::actingAs($this->user);

    $response = $this->postJson('/api/v1/incidents', [...$this->payload, 'start_datetime' => 'invalid-date']);

    $response->assertStatus(422)->assertJsonValidationErrors(['start_datetime']);
});

it('fails validation when end datetime is invalid', function () {
    $this->user->givePermissionTo('INCIDENTS_CREATE');

    Sanctum::actingAs($this->user);

    $response = $this->postJson('/api/v1/incidents', [...$this->payload, 'end_datetime' => 'invalid-date']);

    $response->assertStatus(422)->assertJsonValidationErrors(['end_datetime']);
});

it('fails validation when is major is invalid', function () {
    $this->user->givePermissionTo('INCIDENTS_CREATE');

    Sanctum::actingAs($this->user);

    $response = $this->postJson('/api/v1/incidents', [...$this->payload, 'is_major' => 'invalid']);

    $response->assertStatus(422)->assertJsonValidationErrors(['is_major']);
});

it('fails validation when user does not exist', function () {
    $this->user->givePermissionTo('INCIDENTS_CREATE');

    Sanctum::actingAs($this->user);

    $response = $this->postJson('/api/v1/incidents', [...$this->payload, 'user_id' => 999999]);

    $response->assertStatus(422)->assertJsonValidationErrors(['user_id']);
});

it('fails validation when children incidents is not array', function () {

    $this->user->givePermissionTo('INCIDENTS_CREATE');

    Sanctum::actingAs($this->user);

    $response = $this->postJson('/api/v1/incidents', [...$this->payload, 'children_incidents' => 'invalid']);

    $response->assertStatus(422)->assertJsonValidationErrors(['children_incidents']);
});

it('fails validation when child incident does not exist', function () {
    $this->user->givePermissionTo('INCIDENTS_CREATE');

    Sanctum::actingAs($this->user);

    $response = $this->postJson('/api/v1/incidents', [...$this->payload, 'children_incidents' => [999999]]);

    $response->assertStatus(422)->assertJsonValidationErrors(['children_incidents.0']);
});

it('shows an incident', function () {
    $this->user->givePermissionTo('INCIDENTS_LIST');

    Sanctum::actingAs($this->user);

    $incident = Incident::factory()->create(['identifier' => 'SHOW-INCIDENT']);

    $response = $this->getJson("/api/v1/incidents/{$incident->id}");

    $response->assertStatus(200)->assertJsonFragment(['identifier' => 'SHOW-INCIDENT']);
});

it('fails showing incident without permission', function () {
    Sanctum::actingAs($this->user);

    $incident = Incident::factory()->create();

    $response = $this->getJson("/api/v1/incidents/{$incident->id}");

    $response->assertStatus(403);
});

it('updates an incident', function () {
    $this->user->givePermissionTo('INCIDENTS_UPDATE');

    Sanctum::actingAs($this->user);

    $incident = Incident::factory()->create(['identifier' => 'OLD-ID']);

    $response = $this->patchJson("/api/v1/incidents/{$incident->id}", ['identifier' => 'UPDATED-ID']);

    $response->assertStatus(200)->assertJsonFragment(['identifier' => 'UPDATED-ID']);

    $this->assertDatabaseHas('incidents', [
        'id' => $incident->id,
        'identifier' => 'UPDATED-ID'
    ]);
});

it('fails updating incident without permission', function () {
    Sanctum::actingAs($this->user);

    $incident = Incident::factory()->create();

    $response = $this->patchJson("/api/v1/incidents/{$incident->id}", ['identifier' => 'NEW']);

    $response->assertStatus(403);
});

it('updates children incidents', function () {
    $this->user->givePermissionTo('INCIDENTS_UPDATE');

    Sanctum::actingAs($this->user);

    $parent = Incident::factory()->create(['is_major' => true]);

    $oldChild = Incident::factory()->create(['incident_id' => $parent->id]);

    $newChild = Incident::factory()->create();

    $response = $this->patchJson("/api/v1/incidents/{$parent->id}", [
        'is_major' => true,
        'children_incidents' => [$newChild->id]
    ]);

    $response->assertStatus(200);

    $this->assertDatabaseHas('incidents', ['id' => $oldChild->id, 'incident_id' => null]);

    $this->assertDatabaseHas('incidents', ['id' => $newChild->id, 'incident_id' => $parent->id]);
});

it('fails update validation when identifier already exists', function () {
    $this->user->givePermissionTo('INCIDENTS_UPDATE');

    Sanctum::actingAs($this->user);

    Incident::factory()->create(['identifier' => 'USED-ID']);

    $incident = Incident::factory()->create();

    $response = $this->patchJson("/api/v1/incidents/{$incident->id}", ['identifier' => 'USED-ID']);

    $response->assertStatus(422)->assertJsonValidationErrors(['identifier']);
});

it('deletes an incident', function () {
    $this->user->givePermissionTo('INCIDENTS_DELETE');

    Sanctum::actingAs($this->user);

    $incident = Incident::factory()->create();

    $response = $this->deleteJson("/api/v1/incidents/{$incident->id}");

    $response->assertStatus(200);

    $this->assertSoftDeleted('incidents', ['id' => $incident->id]);
});

it('fails deleting incident without permission', function () {
    Sanctum::actingAs($this->user);

    $incident = Incident::factory()->create();

    $response = $this->deleteJson("/api/v1/incidents/{$incident->id}");

    $response->assertStatus(403);
});
