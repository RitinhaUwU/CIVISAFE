<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserCreateRequest extends FormRequest
{
    public function rules(): array
    {
        $is_patch = $this->isMethod('PATCH');

        return [
            'name' => [$is_patch ? 'sometimes' : 'required'],
            'email' => [$is_patch ? 'sometimes' : 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->route('user'))],
            'password' => [$is_patch ? 'sometimes' : 'required', 'string', 'min:8', 'confirmed'],
            'mobile' => [$is_patch ? 'sometimes' : 'required'],
            'locked' => [$is_patch ? 'sometimes' : 'required', 'boolean'],
            'role' => [$is_patch ? 'sometimes' : 'required', 'string', 'exists:roles,name'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
