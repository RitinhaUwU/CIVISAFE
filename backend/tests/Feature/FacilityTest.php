<?php

use App\Models\User;
use App\Models\Facility;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
});

it('lists facilities', function () {
    $user = User::factory()->create(['locked' => false]);
    $user->givePermissionTo('FACILITIES_LIST');
    Sanctum::actingAs($user);

    Facility::factory()->count(3)->create();

    $response = $this->getJson('/api/v1/facilities');

    $response->assertStatus(200)->assertJsonStructure(['data', 'links', 'meta']);
});

it('fails listing facilities without permission', function () {
    $user = User::factory()->create(['locked' => false]);
    Sanctum::actingAs($user);

    $response = $this->getJson('/api/v1/facilities');

    $response->assertStatus(403);
});

it('filters facilities by search', function () {
    $user = User::factory()->create(['locked' => false]);
    $user->givePermissionTo('FACILITIES_LIST');
    Sanctum::actingAs($user);

    Facility::factory()->create(['name' => 'Pavilhão Central']);
    Facility::factory()->create(['name' => 'Armazém Norte']);

    $response = $this->getJson('/api/v1/facilities?filter[search]=Pavi');

    $response->assertStatus(200)->assertJsonFragment(['name' => 'Pavilhão Central'])->assertJsonMissing(['name' => 'Armazém Norte']);
});

it('shows a facility', function () {
    $user = User::factory()->create(['locked' => false]);
    $user->givePermissionTo('FACILITIES_LIST');
    Sanctum::actingAs($user);

    $facility = Facility::factory()->create(['name' => 'Sala de Reuniões']);

    $response = $this->getJson("/api/v1/facilities/{$facility->id}");

    $response->assertStatus(200)->assertJsonFragment(['name' => 'Sala de Reuniões']);
});

it('fails showing facility without permission', function () {
    $user = User::factory()->create(['locked' => false]);
    Sanctum::actingAs($user);

    $facility = Facility::factory()->create();

    $response = $this->getJson("/api/v1/facilities/{$facility->id}");

    $response->assertStatus(403);
});

it('creates a facility', function () {
    $user = User::factory()->create(['locked' => false]);
    $user->givePermissionTo('FACILITIES_CREATE');
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/facilities', [
        'name' => 'Ginásio',
        'email' => 'ginasio@example.com',
        'contact' => '+351 912345678',
        'address' => 'Rua Principal, 1',
        'description' => 'Instalação desportiva',
    ]);

    $response->assertStatus(201)->assertJsonFragment(['name' => 'Ginásio']);

    $this->assertDatabaseHas('facilities', ['name' => 'Ginásio']);
});

it('fails creating facility without permission', function () {
    $user = User::factory()->create(['locked' => false]);
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/facilities', ['name' => 'Restrita']);

    $response->assertStatus(403);
});

it('fails validation when name is missing on create', function () {
    $user = User::factory()->create(['locked' => false]);
    $user->givePermissionTo('FACILITIES_CREATE');
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/facilities', ['description' => 'Sem nome']);

    $response->assertStatus(422)->assertJsonValidationErrors(['name']);
});

it('fails validation when contact is missing on create', function () {
    $user = User::factory()->create(['locked' => false]);
    $user->givePermissionTo('FACILITIES_CREATE');
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/facilities', ['name' => 'Ginásio', 'email' => 'ginasio@example.com']);

    $response->assertStatus(422)->assertJsonValidationErrors(['contact']);
});

it('fails validation when email is missing on create', function () {
    $user = User::factory()->create(['locked' => false]);
    $user->givePermissionTo('FACILITIES_CREATE');
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/facilities', ['name' => 'Ginásio', 'contact' => '+351 912345678']);

    $response->assertStatus(422)->assertJsonValidationErrors(['email']);
});

it('fails validation with invalid email on create', function () {
    $user = User::factory()->create(['locked' => false]);
    $user->givePermissionTo('FACILITIES_CREATE');
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/facilities', ['name' => 'Ginásio', 'contact' => '+351 912345678', 'email' => 'email-invalido']);

    $response->assertStatus(422)->assertJsonValidationErrors(['email']);
});

it('fails validation with invalid contact on create', function () {
    $user = User::factory()->create(['locked' => false]);
    $user->givePermissionTo('FACILITIES_CREATE');
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/facilities', ['name' => 'Ginásio', 'contact' => 'abc123', 'email' => 'ginasio@example.com']);

    $response->assertStatus(422)->assertJsonValidationErrors(['contact']);
});

it('updates a facility', function () {
    $user = User::factory()->create(['locked' => false]);
    $user->givePermissionTo('FACILITIES_UPDATE');
    Sanctum::actingAs($user);

    $facility = Facility::factory()->create(['name' => 'Antiga Sala']);

    $response = $this->patchJson("/api/v1/facilities/{$facility->id}", ['name' => 'Nova Sala']);

    $response->assertStatus(200)->assertJsonFragment(['name' => 'Nova Sala']);

    $this->assertDatabaseHas('facilities', ['id' => $facility->id, 'name' => 'Nova Sala']);
});

it('fails updating facility without permission', function () {
    $user = User::factory()->create(['locked' => false]);
    Sanctum::actingAs($user);

    $facility = Facility::factory()->create();

    $response = $this->patchJson("/api/v1/facilities/{$facility->id}", ['name' => 'Tentativa']);

    $response->assertStatus(403);
});

it('fails update validation with invalid email', function () {
    $user = User::factory()->create(['locked' => false]);
    $user->givePermissionTo('FACILITIES_UPDATE');
    Sanctum::actingAs($user);

    $facility = Facility::factory()->create();

    $response = $this->patchJson("/api/v1/facilities/{$facility->id}", ['email' => 'email-invalido']);

    $response->assertStatus(422)->assertJsonValidationErrors(['email']);
});

it('fails update validation with invalid contact', function () {
    $user = User::factory()->create(['locked' => false]);
    $user->givePermissionTo('FACILITIES_UPDATE');
    Sanctum::actingAs($user);

    $facility = Facility::factory()->create();

    $response = $this->patchJson("/api/v1/facilities/{$facility->id}", ['contact' => 'abc123']);

    $response->assertStatus(422)->assertJsonValidationErrors(['contact']);
});

it('deletes a facility', function () {
    $user = User::factory()->create(['locked' => false]);
    $user->givePermissionTo('FACILITIES_DELETE');
    Sanctum::actingAs($user);

    $facility = Facility::factory()->create();

    $response = $this->deleteJson("/api/v1/facilities/{$facility->id}");

    $response->assertStatus(200);

    $this->assertSoftDeleted('facilities', ['id' => $facility->id]);
});

it('fails deleting facility without permission', function () {
    $user = User::factory()->create(['locked' => false]);
    Sanctum::actingAs($user);

    $facility = Facility::factory()->create();

    $response = $this->deleteJson("/api/v1/facilities/{$facility->id}");

    $response->assertStatus(403);
});

it('generates a signed url for image upload', function () {
    $user = User::factory()->create(['locked' => false]);
    $user->givePermissionTo('FACILITIES_UPDATE');
    Sanctum::actingAs($user);

    Storage::fake('data_bucket');

    $response = $this->postJson('/api/v1/facilities/uploadUrl', ['filename' => 'imagem.jpg',]);

    $response->assertStatus(200)->assertJsonStructure(['key', 'url']);
});

it('fails signed url with invalid extension', function () {
    $user = User::factory()->create(['locked' => false]);
    $user->givePermissionTo('FACILITIES_UPDATE');
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/facilities/uploadUrl', ['filename' => 'imagem.pdf',]);

    $response->assertStatus(422)->assertJsonValidationErrors(['filename']);
});

it('fails signed url when filename is missing', function () {
    $user = User::factory()->create(['locked' => false]);
    $user->givePermissionTo('FACILITIES_UPDATE');
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/facilities/uploadUrl', []);

    $response->assertStatus(422)->assertJsonValidationErrors(['filename']);
});

it('confirms image upload for facility', function () {
    $user = User::factory()->create(['locked' => false]);
    $user->givePermissionTo('FACILITIES_UPDATE');
    Sanctum::actingAs($user);

    Storage::fake('data_bucket');

    $facility = Facility::factory()->create();
    $key = '/facilities/images/test-uuid.jpg';

    Storage::disk('data_bucket')->put($key, 'fake-image');

    $response = $this->postJson("/api/v1/facilities/{$facility->id}/upload", ['key' => $key]);

    $response->assertStatus(200);

    $this->assertDatabaseHas('facilities', ['id' => $facility->id, 'image' => $key]);
});

it('deletes old image when confirming new upload', function () {
    $user = User::factory()->create(['locked' => false]);
    $user->givePermissionTo('FACILITIES_UPDATE');
    Sanctum::actingAs($user);

    Storage::fake('data_bucket');

    $oldKey = '/facilities/images/old.png';
    $newKey = '/facilities/images/new.jpg';

    Storage::disk('data_bucket')->put($oldKey, 'old-image');
    Storage::disk('data_bucket')->put($newKey, 'new-image');

    $facility = Facility::factory()->create(['image' => $oldKey]);

    $response = $this->postJson("/api/v1/facilities/{$facility->id}/upload", ['key' => $newKey]);

    $response->assertStatus(200);

    Storage::disk('data_bucket')->assertMissing($oldKey);
    Storage::disk('data_bucket')->assertExists($newKey);

    $this->assertDatabaseHas('facilities', ['id' => $facility->id, 'image' => $newKey]);
});

it('fails confirm upload with invalid extension', function () {
    $user = User::factory()->create(['locked' => false]);
    $user->givePermissionTo('FACILITIES_UPDATE');
    Sanctum::actingAs($user);

    $facility = Facility::factory()->create();

    $response = $this->postJson("/api/v1/facilities/{$facility->id}/upload", ['key' => '/facilities/images/ficheiro.pdf',]);

    $response->assertStatus(500);
});

it('uploads documents to a facility', function () {
    $user = User::factory()->create(['locked' => false]);
    $user->givePermissionTo('FACILITIES_UPDATE');
    Sanctum::actingAs($user);

    Storage::fake('facilities_documents');

    $facility = Facility::factory()->create();

    $response = $this->postJson("/api/v1/facilities/{$facility->id}/documents", [
        'files' => [
            UploadedFile::fake()->create('relatorio.pdf', 1024, 'application/pdf'),
            UploadedFile::fake()->image('foto.jpg')
        ]
    ]);

    $response->assertStatus(200);

    expect($facility->fresh()->getMedia('documents'))->toHaveCount(2);
});

it('fails uploading documents with invalid mime type', function () {
    $user = User::factory()->create(['locked' => false]);
    $user->givePermissionTo('FACILITIES_UPDATE');
    Sanctum::actingAs($user);

    $facility = Facility::factory()->create();

    $response = $this->postJson("/api/v1/facilities/{$facility->id}/documents", [
        'files' => [
            UploadedFile::fake()->create('script.exe', 100, 'application/octet-stream')
        ]
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['files.0']);
});

it('fails uploading documents when files array is missing', function () {
    $user = User::factory()->create(['locked' => false]);
    $user->givePermissionTo('FACILITIES_UPDATE');
    Sanctum::actingAs($user);

    $facility = Facility::factory()->create();

    $response = $this->postJson("/api/v1/facilities/{$facility->id}/documents", []);

    $response->assertStatus(422)->assertJsonValidationErrors(['files']);
});

it('fails uploading document that exceeds max size', function () {
    $user = User::factory()->create(['locked' => false]);
    $user->givePermissionTo('FACILITIES_UPDATE');
    Sanctum::actingAs($user);

    $facility = Facility::factory()->create();

    $response = $this->postJson("/api/v1/facilities/{$facility->id}/documents", [
        'files' => [
            UploadedFile::fake()->create('grande.pdf', 25000, 'application/pdf') // 25MB > 20MB
        ]
    ]);

    $response->assertStatus(422)->assertJsonValidationErrors(['files.0']);
});

it('downloads a document from a facility', function () {
    $user = User::factory()->create(['locked' => false]);
    $user->givePermissionTo('FACILITIES_LIST');
    Sanctum::actingAs($user);

    Storage::fake('facilities_documents');

    $facility = Facility::factory()->create();
    $media = $facility->addMediaFromString('conteúdo do ficheiro')->usingFileName('doc.pdf')->toMediaCollection('documents');

    $response = $this->getJson("/api/v1/facilities/{$facility->id}/documents/{$media->id}/download");

    $response->assertStatus(200);
});

it('returns 404 when downloading non-existent document', function () {
    $user = User::factory()->create(['locked' => false]);
    $user->givePermissionTo('FACILITIES_LIST');
    Sanctum::actingAs($user);

    $facility = Facility::factory()->create();

    $response = $this->getJson("/api/v1/facilities/{$facility->id}/documents/99999/download");

    $response->assertStatus(404);
});

it('deletes a document from a facility', function () {
    $user = User::factory()->create(['locked' => false]);
    $user->givePermissionTo('FACILITIES_UPDATE');
    Sanctum::actingAs($user);

    Storage::fake('facilities_documents');

    $facility = Facility::factory()->create();
    $media = $facility->addMediaFromString('conteúdo')->usingFileName('doc.pdf')->toMediaCollection('documents');

    $response = $this->deleteJson("/api/v1/facilities/{$facility->id}/documents/{$media->id}");

    $response->assertStatus(200);

    expect($facility->fresh()->getMedia('documents'))->toHaveCount(0);
});

it('returns 404 when deleting non-existent document', function () {
    $user = User::factory()->create(['locked' => false]);
    $user->givePermissionTo('FACILITIES_UPDATE');
    Sanctum::actingAs($user);

    $facility = Facility::factory()->create();

    $response = $this->deleteJson("/api/v1/facilities/{$facility->id}/documents/99999");

    $response->assertStatus(404);
});


