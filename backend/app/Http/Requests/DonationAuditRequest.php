<?php

namespace App\Http\Requests;

use App\Models\User;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class DonationAuditRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'adjustment_type' => ['required', 'string', 'in:add,remove'],
            'category_id' => ['required', 'exists:donation_goods_types,id'],
            'quantity' => ['required', 'numeric:strict'],
            'reason' => ['required', 'string', 'in:diffCorrection,brokenItem,lost,other'],
            'obs' => ['sometimes', 'nullable', 'string', 'max:400000000'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
