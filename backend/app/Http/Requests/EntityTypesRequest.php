<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EntityTypesRequest extends FormRequest
{
    public function rules(): array
    {
        $is_patch = $this->isMethod('PATCH');

        return [
            'name' => [$is_patch ? 'sometimes' : 'required'],
            'description' => ['sometimes', 'nullable'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
