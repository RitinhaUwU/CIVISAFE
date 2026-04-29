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
            'uuid' => $request->uuid,
            'title' => $request->title,
            'body' => $request->body,
            'date' => $request->created_at,
            'read' => $request->read_at ? true : false
        ];
    }
}
