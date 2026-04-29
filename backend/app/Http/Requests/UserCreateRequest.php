<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserCreateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string'],
            'email' => ['sometimes', 'required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['sometimes', 'required', 'string', 'min:8', 'confirmed'],
            'mobile' => ['sometimes', 'required', 'regex:/^\+?[0-9]+(?: [0-9]+)*$/'],
            'locked' => ['sometimes', 'required', 'boolean'],
            'role' => ['sometimes', 'required', 'string', 'exists:roles,name'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
