<?php

namespace App\Http\Resources;

use App\Models\Incident;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

/** @mixin Incident */
class IncidentResource extends JsonApiResource
{

    public $attributes = [
      'id',
      'identifier',
      'is_major',
      'start_datetime',
      'end_datetime',

      'coordinates',
      'common_place',
      'address',
      'parish',
      'municipality',
      'district',

      'command_post',

      'alert_source_name',
      'alert_source_contact',

      'obs',
      'created_at',
      'updated_at'
    ];

    public $relationships = [
        'category'         => CategoryResource::class,
        'incidentState'    => IncidentStateResource::class,
        'incidentPriority' => IncidentPriorityResource::class,
        'resources'        => IncidentEntitiesMappingResource::class,
        'parentIncident'   => IncidentResource::class,
//        'user'             => UserResource::class,
    ];

    public function toLinks(Request $request)
    {
        return [
            'self' => url('/api/v1/incidents/' . $this->id),
        ];
    }
}
