<?php

namespace App\Http\Resources\Equipment;

use App\Models\EquipmentEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin EquipmentEvent */
class EquipmentEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'date' => $this->date,
            'event' => $this->event,
            'description' => $this->description,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'equipment_id' => $this->equipment_id,

            'equipment' => new EquipmentResource($this->whenLoaded('equipment')),
        ];
    }
}
