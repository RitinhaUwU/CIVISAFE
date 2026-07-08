<?php

namespace App\Http\Requests\Incidents;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IncidentRequest extends FormRequest
{
    public function rules(): array
    {
        $is_patch = $this->isMethod('PATCH');

        return [
            'identifier' => [$is_patch ? 'sometimes' : 'nullable', 'string',
                Rule::requiredIf(fn () => $this->has('is_major') ? $this->boolean('is_major') : (bool) optional($this->route('incident'))->is_major),
                Rule::unique('incidents', 'identifier')->ignore($this->route('incident')),
            ],
            'incident_type_id' => [$is_patch ? 'sometimes' : 'required', 'exists:incident_types,id'],
            'incident_state_id' => [$is_patch ? 'sometimes' : 'required', 'exists:incident_states,id'],
            'incident_priority_id' => [$is_patch ? 'sometimes' : 'required', 'exists:incident_priorities,id'],
            'start_datetime' => [$is_patch ? 'sometimes' : 'required', 'date'],
            'end_datetime' => [$is_patch ? 'sometimes' : 'nullable', 'date'],
            'operational_grid' => [$is_patch ? 'sometimes' : 'nullable', 'string', 'min:1'],
            'coordinates' => [$is_patch ? 'sometimes' : 'nullable'],
            'common_place' => [$is_patch ? 'sometimes' : 'nullable', 'string', 'min:1'],
            'address' => [$is_patch ? 'sometimes' : 'nullable', 'string', 'min:1'],
            'parish' => [$is_patch ? 'sometimes' : 'nullable', 'string', 'min:1'],
            'municipality' => [$is_patch ? 'sometimes' : 'nullable', 'string', 'min:1'],
            'district' => [$is_patch ? 'sometimes' : 'nullable', 'string', 'min:1'],
            'is_major' => [$is_patch ? 'sometimes' : 'required', 'boolean'],
            'alert_source_relationship' => [$is_patch ? 'sometimes' : 'nullable'],
            'alert_source_name' => [$is_patch ? 'sometimes' : 'nullable'],
            'alert_source_contact' => [$is_patch ? 'sometimes' : 'nullable'],
            'obs' => [$is_patch ? 'sometimes' : 'nullable', 'string', 'min:1'],
            'incident_id' => [$is_patch ? 'sometimes' : 'nullable', 'nullable', 'exists:incidents,id'],
            'children_incidents' => ['sometimes', 'array'],
            'children_incidents.*' => ['integer', 'exists:incidents,id'],
            'user_id' => [$is_patch ? 'sometimes' : 'required', 'exists:users,id'],
            'coordinates_pco' => [$is_patch ? 'sometimes' : 'nullable'],
            'name_pco' => [$is_patch ? 'sometimes' : 'nullable'],
        ];
    }

    public function prepareForValidation(): void{
        if ($this->boolean('is_major')) {
            $this->merge(['incident_id' => null]);
        }
        else{
            $this->merge(['identifier' => null]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }
}
