<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IncidentPCORequest extends FormRequest
{
    public function rules(): array
    {
        $is_patch = $this->isMethod('PATCH');

        return [
            'function_pco' => [$is_patch ? 'sometimes' : 'nullable', 'string', 'min:1'],
            'resp_pco' => [$is_patch ? 'sometimes' : 'nullable', 'string', 'min:1'],
            'category_pco' => [$is_patch ? 'sometimes' : 'nullable', 'string', 'min:1'],
            'contact1_pco' => [$is_patch ? 'sometimes' : 'nullable', 'regex:/^\+?[0-9]+(?: [0-9]+)*$/'],
            'contact2_pco' => [$is_patch ? 'sometimes' : 'nullable', 'regex:/^\+?[0-9]+(?: [0-9]+)*$/'],
            'localization_pco' => [$is_patch ? 'sometimes' : 'nullable', 'string', 'min:1'],
            'rob_pco' => [$is_patch ? 'sometimes' : 'nullable', 'string', 'min:1'],
            'srp_pco' => [$is_patch ? 'sometimes' : 'nullable', 'string', 'min:1'],
            'activation_pco_datetime' => [$is_patch ? 'sometimes' : 'nullable', 'date'],
            'start_pco_datetime' => [$is_patch ? 'sometimes' : 'nullable', 'date'],
            'end_pco_datetime' => [$is_patch ? 'sometimes' : 'nullable', 'date'],
            'incident_id' => [$is_patch ? 'sometimes' : 'nullable', 'exists:incidents,id'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
