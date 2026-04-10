<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IncidentTypeRequest extends FormRequest
{
    public function rules(): array
    {
        $is_patch = $this->isMethod('PATCH');

        return [
            'code' => [$is_patch ? 'sometimes' : 'required', 'integer', 'unique:incident_types,code'],
            'species' => [$is_patch ? 'sometimes' : 'required'],
            'type' => [$is_patch ? 'sometimes' : 'required'],
            'description' => [$is_patch ? 'sometimes' : 'nullable'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
