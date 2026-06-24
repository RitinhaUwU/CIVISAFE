<?php

namespace App\Http\Requests\Donations;

use Illuminate\Foundation\Http\FormRequest;

class DonationLogRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'date' => ['required', 'date', 'date_format:Y-m-d'],
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'contact' => ['required', 'regex:/^\+?[0-9]+(?: [0-9]+)*$/'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'donor_type' => ['required', 'in:single,company,org,misc'],
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
