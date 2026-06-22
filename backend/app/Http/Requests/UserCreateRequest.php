<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserCreateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'min:1'],
            'email' => ['sometimes', 'required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['sometimes', 'required', 'string', 'min:8', 'confirmed'],
            'mobile' => ['sometimes', 'required', 'regex:/^\+?[0-9]+(?: [0-9]+)*$/'],
            'locked' => ['sometimes', 'required', 'boolean'],
            'role' => ['sometimes', 'required', 'string', 'in:admin,user'],
            'module_incidents' => [
                'sometimes',
                'required_if:role,user',
                'boolean'
            ],
            'module_volunteers'=> [
                'sometimes',
                'required_if:role,user',
                'boolean'
            ],
            'module_donations' => [
                'sometimes',
                'required_if:role,user',
                'boolean'
            ],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
