<?php

namespace App\Http\Resources\Incident;

use App\Models\IncidentPriority;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin IncidentPriority */
class IncidentPriorityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'hex_color' => $this->hex_color,
            'is_active' => $this->is_active,
        ];
    }
}
