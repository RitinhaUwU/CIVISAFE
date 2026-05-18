<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IncidentRequest extends FormRequest
{
    public function rules(): array
    {
        $is_patch = $this->isMethod('PATCH');

        return [
            'identifier' => [$is_patch ? 'sometimes' : 'required'],
            'incident_type_id' => [$is_patch ? 'sometimes' : 'required', 'exists:incident_types,id'],
            'incident_state_id' => [$is_patch ? 'sometimes' : 'required', 'exists:incident_states,id'],
            'incident_priority_id' => [$is_patch ? 'sometimes' : 'required', 'exists:incident_priorities,id'],
            'user_id' => [$is_patch ? 'sometimes' : 'required', 'exists:users,id'],
            'start_datetime' => [$is_patch ? 'sometimes' : 'required', 'date'],
            'end_datetime' => [$is_patch ? 'sometimes' : 'nullable', 'date'],
            'coordinates' => [$is_patch ? 'sometimes' : 'nullable'],
            'common_place' => [$is_patch ? 'sometimes' : 'nullable', 'string', 'min:1'],
            'address' => [$is_patch ? 'sometimes' : 'nullable', 'string', 'min:1'],
            'parish' => [$is_patch ? 'sometimes' : 'nullable', 'string', 'min:1'],
            'municipality' => [$is_patch ? 'sometimes' : 'nullable', 'string', 'min:1'],
            'district' => [$is_patch ? 'sometimes' : 'nullable', 'string', 'min:1'],
            'command_post' => [$is_patch ? 'sometimes' : 'required', 'string', 'min:1'], //???????
            'is_major' => [$is_patch ? 'sometimes' : 'required', 'boolean'],
            'alert_source_relationship' => [$is_patch ? 'sometimes' : 'nullable'],
            'alert_source_name' => [$is_patch ? 'sometimes' : 'nullable'],
            'alert_source_contact' => [$is_patch ? 'sometimes' : 'nullable'],
            'obs' => [$is_patch ? 'sometimes' : 'nullable', 'string', 'min:1'],
            'incident_id' => [$is_patch ? 'sometimes' : 'nullable', 'integer'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
