<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IncidentPriorityRequest extends FormRequest
{
    public function rules(): array
    {
        $is_patch = $this->isMethod('PATCH');

        return [
            'name' => [$is_patch ? 'sometimes' : 'required'],
            'description' => [$is_patch ? 'sometimes' : 'required'],
            'hex_color' => [$is_patch ? 'sometimes' : 'required'],
            'is_active' => [$is_patch ? 'sometimes' : 'required', 'boolean'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
