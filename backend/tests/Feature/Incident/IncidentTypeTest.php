<?php

use App\Models\User;
use App\Models\IncidentType;
use Illuminate\Http\UploadedFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
});

it('lists incident types', function () {

    $user = User::factory()->create(['locked' => false]);
    $user->givePermissionTo('INCIDENT_TYPES_LIST');

    Sanctum::actingAs($user);

    DB::table('incident_types')->insert([
        'code' => 100,
        'species' => 'Fire',
        'type' => 'Urban',
        'description' => 'Test',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $response = $this->getJson('/api/v1/incidentTypes');

    $response->assertStatus(200)->assertJsonStructure(['data', 'links', 'meta']);
});

it('fails listing incident types without permission', function () {

    $user = User::factory()->create(['locked' => false]);

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/v1/incidentTypes');

    $response->assertStatus(403);
});

it('filters incident types by search', function () {

    $user = User::factory()->create(['locked' => false]);
    $user->givePermissionTo('INCIDENT_TYPES_LIST');

    Sanctum::actingAs($user);

    DB::table('incident_types')->insert([
        'code' => 100,
        'species' => 'Fire',
        'type' => 'Urban',
        'description' => 'Test',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('incident_types')->insert([
        'code' => 200,
        'species' => 'Medical',
        'type' => 'Rescue',
        'description' => 'Test',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $response = $this->getJson('/api/v1/incidentTypes?filter[search]=Fire');

    $response->assertStatus(200)
        ->assertJsonFragment(['species' => 'Fire'])
        ->assertJsonMissing(['species' => 'Medical']);
});

it('shows an incident type', function () {

    $user = User::factory()->create(['locked' => false]);
    $user->givePermissionTo('INCIDENT_TYPES_LIST');

    Sanctum::actingAs($user);

    DB::table('incident_types')->insert([
        'id' => 1,
        'code' => 100,
        'species' => 'Fire',
        'type' => 'Urban',
        'description' => 'Test',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $response = $this->getJson('/api/v1/incidentTypes/1');

    $response->assertStatus(200)
        ->assertJsonFragment(['code' => 100]);
});

it('fails showing incident type without permission', function () {

    $user = User::factory()->create(['locked' => false]);

    DB::table('incident_types')->insert([
        'id' => 1,
        'code' => 100,
        'species' => 'Fire',
        'type' => 'Urban',
        'description' => 'Test',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/v1/incidentTypes/1');

    $response->assertStatus(403);
});

it('imports incident types file', function () {

    Storage::fake('misc_bucket');

    $user = User::factory()->create(['locked' => false]);
    $user->givePermissionTo('INCIDENT_TYPES_UPLOAD');

    Sanctum::actingAs($user);

    $file = UploadedFile::fake()->create(
        'incident-types.xlsx',
        100,
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    );

    $response = $this->postJson('/api/v1/incidentTypes', ['file' => $file]);

    $response->assertStatus(201)->assertJsonStructure(['message', 'file']);
});

it('fails importing incident types without permission', function () {

    Storage::fake('misc_bucket');

    $user = User::factory()->create(['locked' => false]);

    Sanctum::actingAs($user);

    $file = UploadedFile::fake()->create('file.xlsx', 10);

    $response = $this->postJson('/api/v1/incidentTypes', ['file' => $file]);

    $response->assertStatus(403);
});

it('fails validation when file is missing', function () {

    $user = User::factory()->create(['locked' => false]);
    $user->givePermissionTo('INCIDENT_TYPES_UPLOAD');

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/incidentTypes', []);

    $response->assertStatus(422)->assertJsonValidationErrors(['file']);
});

it('fails validation when file type is invalid', function () {

    $user = User::factory()->create(['locked' => false]);
    $user->givePermissionTo('INCIDENT_TYPES_UPLOAD');

    Sanctum::actingAs($user);

    $file = UploadedFile::fake()->create('file.txt');

    $response = $this->postJson('/api/v1/incidentTypes', ['file' => $file]);

    $response->assertStatus(422)->assertJsonValidationErrors(['file']);
});
