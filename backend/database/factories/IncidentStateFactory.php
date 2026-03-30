<?php

namespace Database\Factories;

use App\Models\IncidentState;
use Illuminate\Database\Eloquent\Factories\Factory;

class IncidentStateFactory extends Factory
{
    protected $model = IncidentState::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'description' => $this->faker->text(),
            'hex_color' => $this->faker->hexColor(),
            'terminates_incident' => $this->faker->boolean(),
            'is_active' => $this->faker->boolean(),
        ];
    }
}
