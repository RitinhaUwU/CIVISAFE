<?php

namespace App\Http\Resources;

use App\Models\EquipmentType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin EquipmentType */
class EquipmentTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'custom_fields' => $this->custom_fields,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
