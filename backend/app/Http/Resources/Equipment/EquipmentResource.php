<?php

namespace App\Http\Resources\Equipment;

use App\Http\Resources\Entity\EntityResource;
use App\Models\Equipment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Equipment */
class EquipmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'custom_fields_data' => $this->custom_fields_data,
            'image' => $this->image,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'equipment_type_id' => $this->equipment_type_id,
            'entity_id' => $this->entity_id,
            'facility_id' => $this->facility_id,

            'equipmentType' => new EquipmentTypeResource($this->whenLoaded('equipmentType')),
            'entity' => new EntityResource($this->whenLoaded('entity')),
        ];
    }
}
