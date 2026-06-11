<?php

namespace App\Http\Requests\Donations;

use Illuminate\Foundation\Http\FormRequest;

class DonationLogRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required'],
            'contact' => ['required'],
            'email' => ['required', 'email', 'max:254'],
            'donator_type' => ['required'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
