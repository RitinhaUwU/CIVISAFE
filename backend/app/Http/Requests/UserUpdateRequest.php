<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        $is_patch = $this->isMethod('PATCH');

        $rules = [
            'name' => [$is_patch ? 'sometimes' : 'required', 'min:1'],
            'email' => [$is_patch ? 'sometimes' : 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->route('user'))],
            'password' => [$is_patch ? 'sometimes' : 'nullable', 'string', 'min:8', 'confirmed'],
            'mobile' => [$is_patch ? 'sometimes' : 'required', 'regex:/^\+?[0-9]+(?: [0-9]+)*$/'],
            'locked' => [$is_patch ? 'sometimes' : 'required', 'boolean'],
            'role' => [$is_patch ? 'sometimes' : 'required', 'string', 'exists:roles,name'],
            'module_incidents' => [
                $is_patch ? 'sometimes' : 'required',
                'required_if:role,user',
                'boolean'
            ],
            'module_volunteers'=> [
                $is_patch ? 'sometimes' : 'required',
                'required_if:role,user',
                'boolean'
            ],
            'module_donations' => [
                $is_patch ? 'sometimes' : 'required',
                'required_if:role,user',
                'boolean'
            ],
        ];

        if (auth()->id() == $this->route('user')->id) {
            $rules['current_password'] = ['required_with:password', 'current_password'];
        }

        return $rules;
    }

    public function authorize(): bool
    {
        return true;
    }
}
