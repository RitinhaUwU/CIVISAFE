<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IncidentPartyRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'incident_id' => ['required', 'exists:incidents'],
            'entity_id' => ['required', 'exists:entities'],
            'vehicle_count' => ['required', 'integer'],
            'human_count' => ['required', 'integer'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
