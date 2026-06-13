<?php

namespace App\Http\Requests\EntityTypes;

use Illuminate\Foundation\Http\FormRequest;

class EntityTypeRequest extends FormRequest
{
    public function rules(): array
    {
        $is_patch = $this->isMethod('PATCH');

        return [
            'name' => [$is_patch ? 'sometimes' : 'required', 'string', 'min:1'],
            'description' => [$is_patch ? 'sometimes' : 'nullable', 'string', 'min:1'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
