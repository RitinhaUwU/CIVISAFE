<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EntityRequest extends FormRequest
{
    public function rules(): array
    {
        $is_patch = $this->isMethod('PATCH');

        return [
            'name' => [$is_patch ? 'sometimes' : 'required'],
            'description' => ['sometimes', 'nullable'],
            'phone_contact' => ['sometimes', 'nullable'],
            'email_contact' => ['sometimes', 'nullable'],
            'address' => ['sometimes', 'nullable'],
            'logo' => ['sometimes', 'nullable'],
            'poc_name' => ['sometimes', 'nullable'],
            'poc_phone' => ['sometimes', 'nullable'],
            'poc_email' => ['sometimes', 'nullable', 'email', 'max:254'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
