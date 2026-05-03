<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EquipmentTypeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'custom_fields' => ['required', 'json'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
