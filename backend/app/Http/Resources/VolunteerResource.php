<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VolunteerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'start_datetime' => $this->start_datetime,
            'end_datetime' => $this->end_datetime,
            'contact' => $this->contact,
            'email' => $this->email,
            'num_elements' => $this->num_elements,
            'mission' => $this->mission,
            'team_identification' => $this->team_identification,
            'classification' => $this->classification,
            'has_accommodation' => $this->has_accommodation,
            'location' => $this->location,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,

            'incident' => new IncidentResource($this->whenLoaded('incident')),
        ];
    }
}
