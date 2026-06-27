<?php

namespace App\Http\Requests\Donations;

use Illuminate\Foundation\Http\FormRequest;

class DonationDistributionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'contact' => ['required', 'regex:/^\+?[0-9]+(?: [0-9]+)*$/', 'min_digits:9'],
            'obs' => ['nullable', 'string', 'max:4000000000'],
            'goods' => ['required', 'array', 'min:1'],
            'goods.*.category_id' => ['required', 'exists:donation_goods_types,id', 'integer'],
            'goods.*.quantity' => ['required', 'numeric:strict', 'min:0.1'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
