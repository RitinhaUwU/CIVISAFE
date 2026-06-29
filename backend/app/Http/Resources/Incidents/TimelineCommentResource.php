<?php

namespace App\Http\Resources\Incidents;

use App\Http\Resources\UserResource;
use App\Models\TimelineComment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TimelineComment */
class TimelineCommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'body'        => $this->body,
            'incident_id' => $this->incident_id,
            'datetime' => $this->datetime,
            'created_at'  => $this->created_at,
            'updated_at'  => $this->updated_at,

            'user' => new UserResource($this->whenLoaded('user')),
            'incident' => new IncidentResource($this->whenLoaded('incident')),
        ];
    }
}
