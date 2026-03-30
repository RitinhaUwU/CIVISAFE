<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IncidentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'identifier' => ['required'],
            'category_code' => ['required', 'integer'],
            'incident_state_id' => ['required', 'exists:incident_states'],
            'user_id' => ['required', 'exists:users'],
            'start_datetime' => ['required', 'date'],
            'end_datetime' => ['nullable', 'date'],
            'coordinates' => ['nullable'],
            'common_place' => ['nullable'],
            'address' => ['nullable'],
            'parish' => ['nullable'],
            'municipality' => ['nullable'],
            'district' => ['nullable'],
            'command_post' => ['required'],
            'is_major' => ['boolean'],
            'alert_source_relationship' => ['nullable'],
            'alert_source_name' => ['nullable'],
            'alert_source_contact' => ['nullable'],
            'obs' => ['nullable'],
            'incident_id' => ['nullable', 'integer'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
