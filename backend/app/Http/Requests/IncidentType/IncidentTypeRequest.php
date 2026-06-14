<?php

namespace App\Http\Requests\IncidentType;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IncidentTypeRequest extends FormRequest
{
    public function rules(): array
    {
        $is_patch = $this->isMethod('PATCH');

        return [
            'code' => [$is_patch ? 'sometimes' : 'required', 'integer',
                Rule::unique('incident_types', 'code')
                    ->withoutTrashed()
            ],
            'species' => [$is_patch ? 'sometimes' : 'required', 'string', 'min:1'],
            'type' => [$is_patch ? 'sometimes' : 'required', 'string', 'min:1'],
            'description' => [$is_patch ? 'sometimes' : 'nullable', 'string', 'min:1'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
