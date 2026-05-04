<?php

namespace App\Http\Resources\Incident;

use App\Models\IncidentState;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin IncidentState */
class IncidentStateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'hex_color' => $this->hex_color,
            'terminates_incident' => $this->terminates_incident,
            'is_active' => $this->is_active,
        ];
    }
}
