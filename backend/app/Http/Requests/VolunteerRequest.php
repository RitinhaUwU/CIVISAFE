<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property mixed $classification
 */
class VolunteerRequest extends FormRequest
{
    public function rules(): array
    {
        $is_patch = $this->isMethod('PATCH');

        return [
            'name' => [$is_patch ? 'sometimes' : 'required', 'string', 'min:1'],
            'contact' => [$is_patch ? 'sometimes' : 'required', 'regex:/^\+?[0-9]+(?: [0-9]+)*$/'],
            'email' => [$is_patch ? 'sometimes' : 'required', 'email'],
            'team_identification' => [$is_patch ? 'sometimes' : 'nullable', 'string', 'min:1'],
            'num_elements' => [$is_patch ? 'sometimes' : 'nullable', 'integer', 'min:1'],
            'mission' => [$is_patch ? 'sometimes' : 'nullable', 'string', 'min:1'],
            'classification' => [$is_patch ? 'sometimes' : 'required', 'in:single,org,misc'],
            'start_datetime' => [$is_patch ? 'sometimes' : 'required', 'date'],
            'end_datetime' => [$is_patch ? 'sometimes' : 'nullable', 'date'],
            'has_accommodation' => [$is_patch ? 'sometimes' : 'required', 'boolean'],
            'location' => ['nullable', 'string', 'min:1'],
            'has_meal' => [$is_patch ? 'sometimes' : 'required', 'boolean'],
            'meal_notes' => ['nullable', 'string', 'min:1'],
            'meal_location' => ['nullable', 'string', 'min:1'],
            'incident_id' => [$is_patch ? 'sometimes' : 'nullable', 'integer'],
        ];
    }

    public function prepareForValidation(): void
    {
        if ($this->classification === 'single') {
            $this->merge([ 'num_elements' => 1 ]);
        }

        if ($this->has('has_accommodation') && !$this->boolean('has_accommodation')) {
            $this->merge(['location' => null]);
        }

        if ($this->has('has_meal') && !$this->boolean('has_meal')) {
            $this->merge([
                'meal_notes' => null,
                'meal_location' => null,
            ]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }
}
