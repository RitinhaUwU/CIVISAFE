<?php

use App\Models\Entity;
use App\Models\Incident;
use App\Models\IncidentParty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();

    $this->user = User::factory()->create([
        'locked' => false,
    ]);

    $this->incident = Incident::factory()->create();

    $this->entity = Entity::factory()->create();

    $this->payload = [
        'vehicle_count' => 2,
        'human_count' => 5,
        'entity_id' => $this->entity->id,
    ];
});

it('lists incident parties', function () {
    Sanctum::actingAs($this->user);

    IncidentParty::factory()->count(3)->create(['incident_id' => $this->incident->id]);

    $response = $this->getJson("/api/v1/incidents/{$this->incident->id}/parties");

    $response->assertStatus(200)->assertJsonStructure(['data', 'meta' => ['total_vehicles', 'total_humans',]]);
});

it('calculates total vehicles and humans', function () {
    Sanctum::actingAs($this->user);

    IncidentParty::factory()->create(['incident_id' => $this->incident->id, 'vehicle_count' => 2, 'human_count' => 5]);

    IncidentParty::factory()->create(['incident_id' => $this->incident->id, 'vehicle_count' => 3, 'human_count' => 7]);

    $response = $this->getJson("/api/v1/incidents/{$this->incident->id}/parties");

    $response->assertStatus(200)->assertJsonPath('meta.total_vehicles', 5)->assertJsonPath('meta.total_humans', 12);
});

it('creates an incident party', function () {
    Sanctum::actingAs($this->user);

    $response = $this->postJson("/api/v1/incidents/{$this->incident->id}/parties", $this->payload);

    $response->assertStatus(201)->assertJsonFragment(['vehicle_count' => 2, 'human_count' => 5]);

    $this->assertDatabaseHas('incident_parties', [
        'incident_id' => $this->incident->id,
        'entity_id' => $this->entity->id,
        'vehicle_count' => 2,
        'human_count' => 5,
    ]);
});

it('fails validation when vehicle count is missing', function () {
    Sanctum::actingAs($this->user);

    $payload = $this->payload;

    unset($payload['vehicle_count']);

    $response = $this->postJson("/api/v1/incidents/{$this->incident->id}/parties", $payload);

    $response->assertStatus(422)->assertJsonValidationErrors(['vehicle_count']);
});

it('fails validation when vehicle count is invalid', function () {
    Sanctum::actingAs($this->user);

    $response = $this->postJson("/api/v1/incidents/{$this->incident->id}/parties", [...$this->payload, 'vehicle_count' => 'invalid']);

    $response->assertStatus(422)->assertJsonValidationErrors(['vehicle_count']);
});

it('fails validation when vehicle count is negative', function () {
    Sanctum::actingAs($this->user);

    $response = $this->postJson("/api/v1/incidents/{$this->incident->id}/parties", [...$this->payload, 'vehicle_count' => -1]);

    $response->assertStatus(422)->assertJsonValidationErrors(['vehicle_count']);
});

it('fails validation when human count is missing', function () {
    Sanctum::actingAs($this->user);

    $payload = $this->payload;

    unset($payload['human_count']);

    $response = $this->postJson("/api/v1/incidents/{$this->incident->id}/parties", $payload);

    $response->assertStatus(422)->assertJsonValidationErrors(['human_count']);
});

it('fails validation when human count is invalid', function () {
    Sanctum::actingAs($this->user);

    $response = $this->postJson("/api/v1/incidents/{$this->incident->id}/parties", [...$this->payload, 'human_count' => 'invalid']);

    $response->assertStatus(422)->assertJsonValidationErrors(['human_count']);
});

it('fails validation when human count is negative', function () {
    Sanctum::actingAs($this->user);

    $response = $this->postJson("/api/v1/incidents/{$this->incident->id}/parties", [...$this->payload, 'human_count' => -1]);

    $response->assertStatus(422)->assertJsonValidationErrors(['human_count']);
});

it('fails validation when entity id is missing', function () {
    Sanctum::actingAs($this->user);

    $payload = $this->payload;

    unset($payload['entity_id']);

    $response = $this->postJson("/api/v1/incidents/{$this->incident->id}/parties", $payload);

    $response->assertStatus(422)->assertJsonValidationErrors(['entity_id']);
});

it('fails validation when entity does not exist', function () {
    Sanctum::actingAs($this->user);

    $response = $this->postJson("/api/v1/incidents/{$this->incident->id}/parties", [...$this->payload, 'entity_id' => 999999]);

    $response->assertStatus(422)->assertJsonValidationErrors(['entity_id']);
});

it('updates an incident party', function () {
    Sanctum::actingAs($this->user);

    $party = IncidentParty::factory()->create(['incident_id' => $this->incident->id, 'vehicle_count' => 1, 'human_count' => 2]);

    $response = $this->patchJson("/api/v1/incidents/{$this->incident->id}/parties/{$party->id}", ['vehicle_count' => 10, 'human_count' => 20]);

    $response->assertStatus(200)->assertJsonFragment(['vehicle_count' => 10, 'human_count' => 20]);

    $this->assertDatabaseHas('incident_parties', [
        'id' => $party->id,
        'vehicle_count' => 10,
        'human_count' => 20,
    ]);
});

it('allows partial patch update', function () {
    Sanctum::actingAs($this->user);

    $party = IncidentParty::factory()->create(['incident_id' => $this->incident->id]);

    $response = $this->patchJson("/api/v1/incidents/{$this->incident->id}/parties/{$party->id}", ['vehicle_count' => 99]);

    $response->assertStatus(200)->assertJsonFragment(['vehicle_count' => 99]);

    $this->assertDatabaseHas('incident_parties', [
        'id' => $party->id,
        'vehicle_count' => 99
    ]);
});

it('fails update validation when vehicle count is invalid', function () {
    Sanctum::actingAs($this->user);

    $party = IncidentParty::factory()->create(['incident_id' => $this->incident->id]);

    $response = $this->patchJson("/api/v1/incidents/{$this->incident->id}/parties/{$party->id}", ['vehicle_count' => 'invalid']);

    $response->assertStatus(422)->assertJsonValidationErrors(['vehicle_count']);
});

it('fails update validation when human count is invalid', function () {

    Sanctum::actingAs($this->user);

    $party = IncidentParty::factory()->create(['incident_id' => $this->incident->id]);

    $response = $this->patchJson("/api/v1/incidents/{$this->incident->id}/parties/{$party->id}", ['human_count' => 'invalid']);

    $response->assertStatus(422)->assertJsonValidationErrors(['human_count']);
});

it('fails update validation when entity does not exist', function () {
    Sanctum::actingAs($this->user);

    $party = IncidentParty::factory()->create(['incident_id' => $this->incident->id]);

    $response = $this->patchJson("/api/v1/incidents/{$this->incident->id}/parties/{$party->id}", ['entity_id' => 999999]);

    $response->assertStatus(422)->assertJsonValidationErrors(['entity_id']);
});
