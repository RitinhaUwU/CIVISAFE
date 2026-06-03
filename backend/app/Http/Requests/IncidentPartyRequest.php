<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IncidentPartyRequest extends FormRequest
{
    public function rules(): array
    {
        $is_patch = $this->isMethod('PATCH');

        return [
            'vehicle_count' => [$is_patch ? 'sometimes' : 'required', 'integer'],
            'human_count' => [$is_patch ? 'sometimes' : 'required', 'integer'],
            'incident_id' => [$is_patch ? 'sometimes' : 'required', 'integer', 'exists:incidents,id'],
            'entity_id' => [$is_patch ? 'sometimes' : 'required', 'integer', 'exists:entities,id'],
            'children_entities' => ['sometimes', 'array'],
            'children_entities.*' => ['integer', 'exists:entities,id'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
