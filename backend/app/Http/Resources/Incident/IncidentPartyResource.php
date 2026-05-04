<?php

namespace App\Http\Resources\Incident;

use App\Models\IncidentParty;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin IncidentParty */
class IncidentPartyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [

        ];
    }
}
