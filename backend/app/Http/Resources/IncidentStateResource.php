<?php

namespace App\Http\Resources;

use App\Models\IncidentState;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

/** @mixin IncidentState */
class IncidentStateResource extends JsonApiResource
{
    public $attributes = [
        'id',
        'name',
        'description',
        'hex_color',
        'terminates_incident',
        'is_active',
    ];

    public function toLinks(Request $request)
    {
        return [
            'self' => url('/api/v1/incidentStates/' . $this->id),
        ];
    }
}
