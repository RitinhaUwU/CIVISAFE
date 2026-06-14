<?php

use App\Models\User;
use App\Notifications\GenericNotification;
use App\Enums\NotificationStyles;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
});

it('lists notifications', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->notify( new GenericNotification(
        'Test Notification',
        'This is a test notification',
        NotificationStyles::INFO
    ));

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/v1/notifications');

    $response
        ->assertStatus(200)
        ->assertJsonStructure(['data',])
        ->assertJsonFragment(['title' => 'Test Notification',]);
});

it('shows only authenticated user notifications', function () {

    $user1 = User::factory()->create([
        'locked' => false,
    ]);

    $user2 = User::factory()->create([
        'locked' => false,
    ]);

    $user1->notify( new GenericNotification(
        'User1 Notification',
        'Body',
        NotificationStyles::INFO
    ));

    $user2->notify( new GenericNotification(
        'User2 Notification',
        'Body',
        NotificationStyles::INFO
    ));

    Sanctum::actingAs($user1);

    $response = $this->getJson('/api/v1/notifications');

    $response
        ->assertStatus(200)
        ->assertJsonFragment(['title' => 'User1 Notification',])
        ->assertJsonMissing(['title' => 'User2 Notification',]);
});

it('marks a notification as read', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->notify(new GenericNotification(
        'Unread Notification',
        'Body',
        NotificationStyles::INFO
    ));

    $notification = $user->notifications()->first();

    Sanctum::actingAs($user);

    $response = $this->deleteJson("/api/v1/notifications/{$notification->id}");

    $response
        ->assertStatus(200)
        ->assertJsonFragment(['title' => 'Unread Notification',]);

    expect($notification->fresh()->read_at)->not->toBeNull();
});

it('fails marking another user notification as read', function () {

    $user1 = User::factory()->create([
        'locked' => false,
    ]);

    $user2 = User::factory()->create([
        'locked' => false,
    ]);

    $user2->notify(new GenericNotification(
        'Private Notification',
        'Body',
        NotificationStyles::INFO
    ));

    $notification = $user2->notifications()->first();

    Sanctum::actingAs($user1);

    $response = $this->deleteJson("/api/v1/notifications/{$notification->id}");

    $response->assertStatus(403);
});

it('marks all notifications as read', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    $user->notify(new GenericNotification(
        'Notification 1',
        'Body',
        NotificationStyles::INFO
    ));

    $user->notify(new GenericNotification(
        'Notification 2',
        'Body',
        NotificationStyles::INFO
    ));

    Sanctum::actingAs($user);

    $response = $this->deleteJson('/api/v1/notifications');

    $response->assertStatus(200);

    expect($user->fresh()->unreadNotifications()->count())->toBe(0);
});

it('limits notifications to 30 results', function () {

    $user = User::factory()->create([
        'locked' => false,
    ]);

    for ($i = 1; $i <= 35; $i++) {
        $user->notify( new GenericNotification(
            "Notification {$i}",
            'Body',
            NotificationStyles::INFO
        ));
    }

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/v1/notifications');

    $response->assertStatus(200);

    expect(count($response->json('data')))->toBe(30);
});
