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
            'name' => [$is_patch ? 'required' : 'nullable', 'string'],
            'contact' => [$is_patch ? 'required' : 'nullable', 'regex:/^\+?[0-9]+(?: [0-9]+)*$/'],
            'email' => [$is_patch ? 'required' : 'nullable', 'email'],
            'team_identification' => [$is_patch ? 'sometimes' : 'nullable', 'string'],
            'num_elements' => [$is_patch ? 'sometimes' : 'nullable', 'integer', 'min:1'],
            'mission' => [$is_patch ? 'sometimes' : 'nullable', 'string'],
            'classification' => [$is_patch ? 'required' : 'nullable', 'in:single,org,misc'],
            'start_datetime' => [$is_patch ? 'sometimes' : 'required', 'date'],
            'end_datetime' => [$is_patch ? 'sometimes' : 'nullable', 'date'],
            'has_accommodation' => [$is_patch ? 'sometimes' : 'required', 'boolean'],
            'location' => [$is_patch ? 'sometimes' : 'nullable', 'string'],
            'has_meal' => [$is_patch ? 'sometimes' : 'required', 'boolean'],
            'meal_notes' => [$is_patch ? 'sometimes' : 'nullable', 'string'],
            'meal_location' => [$is_patch ? 'sometimes' : 'nullable', 'string'],
            'incident_id' => [$is_patch ? 'sometimes' : 'nullable', 'integer'],
        ];
    }

    public function prepareForValidation(): void
    {
        if ($this->classification === 'single') {
            $this->merge([ 'num_elements' => 1 ]);
        }

        if (!$this->boolean('has_accommodation')) {
            $this->merge([ 'location' => null ]);
        }

        if (!$this->boolean('has_meal')) {
            $this->merge([ 'meal_notes' => null ]);
            $this->merge([ 'meal_location' => null ]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }
}
