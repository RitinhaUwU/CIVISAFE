<?php

namespace App\Http\Resources\Incident;

use App\Models\Incident;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Incident */
class IncidentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'identifier' => $this->identifier,
            'start_datetime' => $this->start_datetime,
            'end_datetime' => $this->end_datetime,

            'coordinates' => $this->coordinates,
            'common_place' => $this->common_place,
            'address' => $this->address,
            'parish' => $this->parish,
            'municipality' => $this->municipality,
            'district' => $this->district,
            'command_post' => $this->command_post,
            'is_major' => $this->is_major,
            'alert_source_relationship' => $this->alert_source_relationship,
            'alert_source_name' => $this->alert_source_name,
            'alert_source_contact' => $this->alert_source_contact,
            'obs' => $this->obs,
            'incident_id' => $this->incident_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'user_id' => $this->user_id,

            'incidentType' => new IncidentTypeResource($this->whenLoaded('incidentType')),
            'incidentPriority' => new IncidentPriorityResource($this->whenLoaded('incidentPriority')),
            'incidentState' => new IncidentStateResource($this->whenLoaded('incidentState')),
            'parentIncident' => new IncidentResource($this->whenLoaded('parentIncident')),
        ];
    }


}
