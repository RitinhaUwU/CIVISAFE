<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Notifications\DatabaseNotification;

/** @mixin DatabaseNotification */
class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->id,
            'title' => $this->data['title'],
            'body' => $this->data['body'],
            'style' => $this->data['style'],
            'date' => $this->created_at,
            'read' => $this->read_at ? true : false
        ];
    }
}
