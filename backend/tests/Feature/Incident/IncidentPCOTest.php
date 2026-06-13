<?php

use App\Models\Incident;
use App\Models\IncidentPCO;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();

    $this->user = User::factory()->create([
        'locked' => false,
    ]);

    Sanctum::actingAs($this->user);

    $this->incident = Incident::factory()->create();

    $this->payload = [
        'function_pco' => 'Commander',
        'resp_pco' => 'John Doe',
        'category_pco' => 'Operational',
        'contact1_pco' => '+351 912345678',
        'contact2_pco' => '+351 987654321',
        'localization_pco' => 'Lisbon',
        'rob_pco' => 'ROB',
        'srp_pco' => 'SRP',
        'activation_pco_datetime' => now()->toDateTimeString(),
        'start_pco_datetime' => now()->toDateTimeString(),
        'end_pco_datetime' => now()->addHours(2)->toDateTimeString(),
    ];
});

it('lists incident pcos', function () {
    IncidentPCO::factory()->count(3)->create(['incident_id' => $this->incident->id]);

    $response = $this->getJson("/api/v1/incidents/{$this->incident->id}/pco");

    $response->assertStatus(200)->assertJsonCount(3, 'data');
});

it('creates an incident pco', function () {
    $response = $this->postJson("/api/v1/incidents/{$this->incident->id}/pco", $this->payload);

    $response->assertStatus(201)->assertJsonFragment(['function_pco' => 'Commander', 'resp_pco' => 'John Doe']);

    $this->assertDatabaseHas('incident_pcos', [
        'incident_id' => $this->incident->id,
        'function_pco' => 'Commander'
    ]);
});

it('fails validation when function pco is missing', function () {
    $payload = $this->payload;

    unset($payload['function_pco']);

    $response = $this->postJson("/api/v1/incidents/{$this->incident->id}/pco", $payload);

    $response->assertStatus(422)->assertJsonValidationErrors(['function_pco']);
});

it('fails validation when resp pco is missing', function () {
    $payload = $this->payload;

    unset($payload['resp_pco']);

    $response = $this->postJson("/api/v1/incidents/{$this->incident->id}/pco", $payload);

    $response->assertStatus(422)->assertJsonValidationErrors(['resp_pco']);
});

it('fails validation when category pco is missing', function () {
    $payload = $this->payload;

    unset($payload['category_pco']);

    $response = $this->postJson("/api/v1/incidents/{$this->incident->id}/pco", $payload);

    $response->assertStatus(422)->assertJsonValidationErrors(['category_pco']);
});

it('fails validation when contact1 pco is invalid', function () {
    $response = $this->postJson("/api/v1/incidents/{$this->incident->id}/pco", [...$this->payload, 'contact1_pco' => 'invalid-phone']);

    $response->assertStatus(422)->assertJsonValidationErrors(['contact1_pco']);
});

it('fails validation when contact2 pco is invalid', function () {
    $response = $this->postJson("/api/v1/incidents/{$this->incident->id}/pco", [...$this->payload, 'contact2_pco' => 'invalid-phone']);

    $response->assertStatus(422)->assertJsonValidationErrors(['contact2_pco']);
});

it('fails validation when start pco datetime is missing', function () {
    $payload = $this->payload;

    unset($payload['start_pco_datetime']);

    $response = $this->postJson("/api/v1/incidents/{$this->incident->id}/pco", $payload);

    $response->assertStatus(422)->assertJsonValidationErrors(['start_pco_datetime']);
});

it('fails validation when start pco datetime is invalid', function () {
    $response = $this->postJson("/api/v1/incidents/{$this->incident->id}/pco", [...$this->payload, 'start_pco_datetime' => 'invalid-date']);

    $response->assertStatus(422)->assertJsonValidationErrors(['start_pco_datetime']);
});

it('fails validation when end pco datetime is invalid', function () {
    $response = $this->postJson("/api/v1/incidents/{$this->incident->id}/pco", [...$this->payload, 'end_pco_datetime' => 'invalid-date']);

    $response->assertStatus(422)->assertJsonValidationErrors(['end_pco_datetime']);
});

it('fails creating pco when active function already exists', function () {
    IncidentPCO::factory()->create([
        'incident_id' => $this->incident->id,
        'function_pco' => 'Commander',
        'end_pco_datetime' => null
    ]);

    $response = $this->postJson("/api/v1/incidents/{$this->incident->id}/pco", [...$this->payload, 'function_pco' => 'Commander']);

    $response->assertStatus(422)->assertJsonFragment(['type' => 'active_conflict']);
});

it('fails creating pco when there is time overlap', function () {
    IncidentPCO::factory()->create([
        'incident_id' => $this->incident->id,
        'function_pco' => 'Commander',
        'start_pco_datetime' => now(),
        'end_pco_datetime' => now()->addHours(4)
    ]);

    $response = $this->postJson("/api/v1/incidents/{$this->incident->id}/pco", [...$this->payload, 'start_pco_datetime' => now()->addHour()->toDateTimeString(), 'end_pco_datetime' => now()->addHours(2)->toDateTimeString()]);

    $response->assertStatus(422)->assertJsonFragment(['type' => 'overlap']);
});

it('updates an incident pco', function () {
    $pco = IncidentPCO::factory()->create([
        'incident_id' => $this->incident->id,
        'function_pco' => 'Old Function'
    ]);

    $response = $this->patchJson("/api/v1/incidents/{$this->incident->id}/pco/{$pco->id}", ['function_pco' => 'Updated Function']);

    $response->assertStatus(200)->assertJsonFragment(['function_pco' => 'Updated Function']);

    $this->assertDatabaseHas('incident_pcos', ['id' => $pco->id, 'function_pco' => 'Updated Function']);
});

it('fails update validation when contact1 pco is invalid', function () {
    $pco = IncidentPCO::factory()->create(['incident_id' => $this->incident->id]);

    $response = $this->patchJson("/api/v1/incidents/{$this->incident->id}/pco/{$pco->id}", ['contact1_pco' => 'invalid-phone']);

    $response->assertStatus(422)->assertJsonValidationErrors(['contact1_pco']);
});

it('fails updating pco when active function conflict exists', function () {
    IncidentPCO::factory()->create([
        'incident_id' => $this->incident->id,
        'function_pco' => 'Commander',
        'end_pco_datetime' => null
    ]);

    $pco = IncidentPCO::factory()->create([
        'incident_id' => $this->incident->id,
        'function_pco' => 'Different Function'
    ]);

    $response = $this->patchJson("/api/v1/incidents/{$this->incident->id}/pco/{$pco->id}", ['function_pco' => 'Commander', 'start_pco_datetime' => now()->toDateTimeString()]);

    $response->assertStatus(422)->assertJsonFragment(['type' => 'active_conflict']);
});

it('fails updating pco when overlap conflict exists', function () {
    IncidentPCO::factory()->create([
        'incident_id' => $this->incident->id,
        'function_pco' => 'Commander',
        'start_pco_datetime' => now(),
        'end_pco_datetime' => now()->addHours(5)
    ]);

    $pco = IncidentPCO::factory()->create([
        'incident_id' => $this->incident->id,
        'function_pco' => 'Other Function'
    ]);

    $response = $this->patchJson("/api/v1/incidents/{$this->incident->id}/pco/{$pco->id}", ['function_pco' => 'Commander', 'start_pco_datetime' => now()->addHour()->toDateTimeString(), 'end_pco_datetime' => now()->addHours(2)->toDateTimeString()]);

    $response->assertStatus(422)->assertJsonFragment(['type' => 'overlap']);
});
