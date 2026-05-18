<?php

namespace App\Notifications;

use App\Enums\NotificationStyles;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Carbon\Carbon;

class GenericNotification extends Notification implements ShouldBroadcast
{
    use Queueable;

    public function __construct(public string $title, public string $body, public NotificationStyles $style = NotificationStyles::INFO) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'style' => $this->style->value,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'uuid' => $this->id,
            'title' => $this->title,
            'body' => $this->body,
            'style' => $this->style->value,
            'date' => Carbon::now()->toDateTimeString(),
            'read' => false,
        ])->onQueue('notifications');
    }

    public function broadcastAs(): string
    {
        return 'NotificationEvent';
    }
}
