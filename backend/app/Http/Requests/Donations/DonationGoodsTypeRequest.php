<?php

namespace App\Http\Requests\Donations;

use Illuminate\Foundation\Http\FormRequest;

class DonationGoodsTypeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'is_type_countable' => ['required', 'boolean'],
            'unit' => [
                'required_if:is_type_countable,true',
                'prohibited_if:is_type_countable,false',
                'string',
            ],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
