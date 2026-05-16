<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FacilitiesRequest extends FormRequest
{
    public function rules(): array
    {
        $is_patch = $this->isMethod('PATCH');

        return [
            'name' => [$is_patch ? 'sometimes' : 'required', 'min:1'],
            'address' => [$is_patch ? 'sometimes' : 'nullable', 'string', 'min:1'],
            'contact' => [$is_patch ? 'sometimes' : 'required', 'regex:/^\+?[0-9]+(?: [0-9]+)*$/'],
            'email' => [$is_patch ? 'sometimes' : 'required', 'email'],
            'description' => [$is_patch ? 'sometimes' : 'nullable', 'string', 'min:1'],
            'image' => ['nullable'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
