<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    public function rules(): array
    {
        $is_patch = $this->isMethod('PATCH');

        return [
            'name' => [$is_patch ? 'sometimes' : 'required'],
            'email' => [$is_patch ? 'sometimes' : 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user()->id)],
            'password' => [$is_patch ? 'sometimes' : 'required', 'string', 'min:8', 'confirmed'],
            'mobile' => [$is_patch ? 'sometimes' : 'required'],
            'locked' => [$is_patch ? 'sometimes' : 'required', 'boolean'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
