<?php

namespace App\Http\Resources;

use App\Models\IncidentEntitiesMapping;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin IncidentEntitiesMapping */
class IncidentEntitiesMappingResource extends JsonResource
{

    public $attributes = [
        'id',
        'human_resource_amount',
        'vehicle_amount',
        'created_at',
        'updated_at',
    ];

    public $relationships = [
        'entity' => EntityResource::class,
    ];


    public function toLinks(Request $request)
    {
        return [
            'self' => url('/api/v1/incidentPriorities/' . $this->id),
        ];
    }
}
