<?php

namespace App\Http\Resources;

use App\Models\IncidentPCO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin incidentPCO */
class IncidentPCOResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'function_pco' => $this->function_pco,
            'resp_pco' => $this->resp_pco,
            'category_pco' => $this->category_pco,
            'contact1_pco' => $this->contact1_pco,
            'contact2_pco' => $this->contact2_pco,
            'localization_pco' => $this->localization_pco,
            'rob_pco' => $this->rob_pco,
            'srp_pco' => $this->srp_pco,
            'activation_pco_datetime' => $this->activation_pco_datetime,
            'start_pco_datetime' => $this->start_pco_datetime,
            'end_pco_datetime' => $this->end_pco_datetime,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'incidentPCO' => new IncidentResource($this->whenLoaded('incidentPCO')),
        ];
    }
}
