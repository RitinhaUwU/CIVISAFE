<?php

namespace App\Http\Requests\Entity;

use Illuminate\Foundation\Http\FormRequest;

class EntityUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        $is_patch = $this->isMethod('PATCH');

        return [
            'name' => [$is_patch ? 'sometimes' : 'required', 'string'],
            'description' => [$is_patch ? 'sometimes' : 'nullable', 'string'],
            'phone_contact' => [$is_patch ? 'sometimes' : 'nullable', 'regex:/^\+?[0-9]+(?: [0-9]+)*$/'],
            'email_contact' => [$is_patch ? 'sometimes' : 'nullable', 'email', 'max:254'],
            'address' => [$is_patch ? 'sometimes' : 'nullable', 'string'],
            'poc_name' => [$is_patch ? 'sometimes' : 'nullable', 'string'],
            'poc_phone' => [$is_patch ? 'sometimes' : 'nullable', 'regex:/^\+?[0-9]+(?: [0-9]+)*$/'],
            'poc_email' => [$is_patch ? 'sometimes' : 'nullable', 'email', 'max:254'],
            'entity_type_id' => [$is_patch ? 'sometimes' : 'nullable', 'exists:entity_types,id'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
