<?php

namespace App\Http\Resources;

use App\Models\IncidentPriority;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

/** @mixin IncidentPriority */
class IncidentPriorityResource extends JsonApiResource
{

    public $attributes = [
        'id',
        'name',
        'description',
        'hex_color',
        'is_active',
    ];

    public function toLinks(Request $request)
    {
        return [
            'self' => url('/api/v1/incidentPriorities/' . $this->id),
        ];
    }
}
