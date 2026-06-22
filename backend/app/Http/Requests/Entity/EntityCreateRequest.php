<?php

namespace App\Http\Requests\Entity;

use Illuminate\Foundation\Http\FormRequest;

class EntityCreateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'description' => ['nullable', 'string'],
            'phone_contact' => ['nullable', 'regex:/^\+?[0-9]+(?: [0-9]+)*$/'],
            'email_contact' => ['nullable', 'email', 'max:254'],
            'address' => ['nullable', 'string'],
            'poc_name' => ['nullable', 'string'],
            'poc_phone' => ['nullable', 'regex:/^\+?[0-9]+(?: [0-9]+)*$/'],
            'poc_email' => ['nullable', 'email', 'max:254'],
            'entity_type_id' => ['nullable', 'exists:entity_types,id'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
