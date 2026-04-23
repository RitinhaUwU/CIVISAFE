<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IncidentPartyRequest extends FormRequest
{
    public function rules(): array
    {
        $is_patch = $this->isMethod('PATCH');

        return [
            'incident_id' => [$is_patch ? 'sometimes' : 'required', 'exists:incidents'],
            'entity_id' => [$is_patch ? 'sometimes' : 'required', 'exists:entities'],
            'vehicle_count' => [$is_patch ? 'sometimes' : 'required', 'integer'],
            'human_count' => [$is_patch ? 'sometimes' : 'required', 'integer'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
