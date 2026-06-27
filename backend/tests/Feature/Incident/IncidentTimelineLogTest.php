<?php

use App\Models\User;
use App\Models\Entity;
use App\Models\Incident;
use App\Models\IncidentPCO;
use App\Models\IncidentParty;
use App\Models\TimelineComment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Activitylog\Models\Activity;


uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();

    $this->user = User::factory()->create();
});

// INCIDENT

it('creates activity log when incident is created', function () {
    $incident = Incident::factory()->create();

    $activity = Activity::query()->where('log_name', 'incidents')->latest()->first();

    expect($activity)->not->toBeNull()
        ->and($activity->description)->toBe('criou uma ocorrência')
        ->and($activity->subject_type)->toBe(Incident::class)
        ->and($activity->subject_id)->toBe($incident->id);
});

it('creates activity log when incident is updated', function () {
    $incident = Incident::factory()->create(['identifier' => 'INC-001']);
    Activity::query()->delete();
    $incident = $incident->fresh();
    $incident->update(['identifier' => 'INC-002']);

    $activity = Activity::query()->where('log_name', 'incidents')->latest()->first();

    expect($activity->description)->toBe('atualizou uma ocorrência')
        ->and($activity->attribute_changes['attributes']['identifier'])->toBe('INC-002')
        ->and($activity->attribute_changes['old']['identifier'])->toBe('INC-001');
});

it('creates activity log when incident is deleted', function () {
    $incident = Incident::factory()->create();

    Activity::query()->delete();

    $incident->delete();

    $activity = Activity::query()->where('log_name', 'incidents')->latest()->first();

    expect($activity->description)->toBe('eliminou uma ocorrência');
});

// PCO

it('creates activity log when pco is created', function () {
    $incident = Incident::factory()->create();

    $pco = IncidentPCO::factory()->create(['incident_id' => $incident->id]);

    $activity = Activity::query()->where('log_name', 'pcos')->latest()->first();

    expect($activity->description)->toBe('registou uma função no posto de comando')
        ->and($activity->subject_type)->toBe(IncidentPCO::class)
        ->and($activity->subject_id)->toBe($pco->id);
});

it('stores old and new values when pco is updated', function () {
    $pco = IncidentPCO::factory()->create(['resp_pco' => 'Antigo']);
    Activity::query()->delete();
    $pco = $pco->fresh();
    $pco->update(['resp_pco' => 'Novo']);

    $activity = Activity::query()->where('log_name', 'pcos')->latest()->first();

    expect($activity->description)->toBe('atualizou uma função no posto de comando')
        ->and($activity->attribute_changes['attributes']['resp_pco'])->toBe('Novo')
        ->and($activity->attribute_changes['old']['resp_pco'])->toBe('Antigo');
});

// PARTIES

it('creates activity log when party is created', function () {
    $incident = Incident::factory()->create();

    $entity = Entity::factory()->create();

    $party = IncidentParty::factory()->create(['incident_id' => $incident->id, 'entity_id' => $entity->id]);

    $activity = Activity::query()->where('log_name', 'parties')->latest()->first();

    expect($activity->description)->toBe('registou uma equipa')
        ->and($activity->subject_type)->toBe(IncidentParty::class)
        ->and($activity->subject_id)->toBe($party->id);
});

it('creates activity log when party is updated', function () {
    $party = IncidentParty::factory()->create(['vehicle_count' => 2]);
    Activity::query()->delete();
    $party = $party->fresh();
    $party->update(['vehicle_count' => 5]);

    $activity = Activity::query()->where('log_name', 'parties')->latest()->first();

    expect($activity->description)->toBe('atualizou uma equipa')
        ->and($activity->attribute_changes['attributes']['vehicle_count'])->toBe(5)
        ->and($activity->attribute_changes['old']['vehicle_count'])->toBe(2);
});

// COMMENTS

it('creates a timeline comment', function () {
    $incident = Incident::factory()->create();

    Sanctum::actingAs($this->user);

    $response = $this->postJson("/api/v1/incidents/{$incident->id}/comments", ['body' => 'Comentário de teste']);

    $response->assertStatus(201);

    $this->assertDatabaseHas('timeline_comments', ['incident_id' => $incident->id, 'user_id' => $this->user->id, 'body' => 'Comentário de teste']);
});

it('updates a timeline comment', function () {
    $incident = Incident::factory()->create();

    $comment = TimelineComment::factory()->create(['incident_id' => $incident->id, 'user_id' => $this->user->id, 'body' => 'Antigo']);

    Sanctum::actingAs($this->user);

    $response = $this->putJson("/api/v1/incidents/{$incident->id}/comments/{$comment->id}", ['body' => 'Novo']);

    $response->assertStatus(200);

    $this->assertDatabaseHas('timeline_comments', ['id' => $comment->id, 'body' => 'Novo']);
});

// TIMELINE

it('returns merged timeline with logs and comments', function () {
    $this->user->givePermissionTo('INCIDENTS_LIST');

    Sanctum::actingAs($this->user);

    $incident = Incident::factory()->create();

    IncidentPCO::factory()->create(['incident_id' => $incident->id]);

    $entity = Entity::factory()->create();

    IncidentParty::factory()->create(['incident_id' => $incident->id, 'entity_id' => $entity->id]);
    TimelineComment::factory()->create(['incident_id' => $incident->id, 'user_id' => $this->user->id, 'body' => 'Comentário timeline']);

    $response = $this->getJson("/api/v1/incidents/{$incident->id}/timeline");

    $response->assertStatus(200);

    $response->assertJsonFragment(['type' => 'comment', 'body' => 'Comentário timeline']);

    $response->assertJsonFragment(['type' => 'log', 'module' => 'pcos', 'event' => 'registou uma função no posto de comando']);
    $response->assertJsonFragment(['type' => 'log', 'module' => 'parties', 'event' => 'registou uma equipa']);
});

it('returns old and new values for updates', function () {
    $this->user->givePermissionTo('INCIDENTS_LIST');
    Sanctum::actingAs($this->user);

    $incident = Incident::factory()->create(['identifier' => 'INC-001']);
    $incident->update(['identifier' => 'INC-002']);

    $response = $this->getJson("/api/v1/incidents/{$incident->id}/timeline");

    $response->assertStatus(200);

    $response->assertJsonFragment(['event' => 'atualizou uma ocorrência']);

    $response->assertJsonFragment(['identifier' => 'INC-002']);
    $response->assertJsonFragment(['identifier' => 'INC-001']);
});

it('returns timeline ordered by date descending', function () {
    $this->user->givePermissionTo('INCIDENTS_LIST');
    Sanctum::actingAs($this->user);

    $incident = Incident::factory()->create();

    TimelineComment::factory()->create(['incident_id' => $incident->id, 'datetime' => now()->subHour()]);

    TimelineComment::factory()->create(['incident_id' => $incident->id, 'datetime' => now()]);

    $response = $this->getJson("/api/v1/incidents/{$incident->id}/timeline");

    $timeline = $response->json();

    expect(strtotime($timeline[0]['date']))->toBeGreaterThanOrEqual(strtotime($timeline[1]['date']));
});
