<?php

namespace App\Http\Requests\Equipment;

use Illuminate\Foundation\Http\FormRequest;

class EquipmentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required'],
            'equipment_type_id' => ['required', 'exists:equipment_types'],
            'entity_id' => ['nullable', 'exists:entities'],
            'facility_id' => ['nullable', 'exists:facilities'],
            'custom_fields_data' => ['required'],
            'image' => ['nullable'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
