<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IncidentStatesRequest extends FormRequest
{
    public function rules(): array
    {
        $is_patch = $this->isMethod('PATCH');

        return [
            'name' => [$is_patch ? 'sometimes' : 'required', 'min:1'],
            'description' => [$is_patch ? 'sometimes' : 'nullable', 'min:1'],
            'hex_color' => [$is_patch ? 'sometimes' : 'required', 'regex:/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/'],
            'terminates_incident' => [$is_patch ? 'sometimes' : 'required', 'boolean'],
            'is_active' => [$is_patch ? 'sometimes' : 'required', 'boolean'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
