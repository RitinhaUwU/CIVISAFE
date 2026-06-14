<?php

namespace App\Http\Resources;

use App\Models\IncidentParty;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin IncidentParty */
class IncidentPartyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'vehicle_count' => $this->vehicle_count,
            'human_count' => $this->human_count,
            'entity_id' => $this->entity_id,

            'incident' => new IncidentResource($this->whenLoaded('incident')),
            'entity' => new EntityResource($this->whenLoaded('entity')),
        ];
    }
}
