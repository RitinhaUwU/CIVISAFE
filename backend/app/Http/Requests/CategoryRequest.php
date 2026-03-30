<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CategoryRequest extends FormRequest
{
    public function rules(): array
    {
        $is_patch = $this->isMethod('PATCH');

        return [
            'code' => [$is_patch ? 'sometimes' : 'required', 'integer', 'unique:categories,code'],
            'name' => [$is_patch ? 'sometimes' : 'required'],
            'description' => [$is_patch ? 'sometimes' : 'required'],
            'is_active' => ['boolean'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
