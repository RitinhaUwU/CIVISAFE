<?php

namespace App\Http\Requests\Equipment;

use Illuminate\Foundation\Http\FormRequest;

class EquipmentEventRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'equipment_id' => ['required', 'exists:equipment'],
            'date' => ['required', 'date'],
            'event' => ['required'],
            'description' => ['nullable'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
