<?php

namespace Database\Factories;

use App\Models\IncidentPriority;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class IncidentPriorityFactory extends Factory
{
    protected $model = IncidentPriority::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->word() . $this->faker->randomNumber(2, true),
            'description' => $this->faker->text(),
            'hex_color' => $this->faker->hexColor(),
            'is_active' => $this->faker->boolean(),
        ];
    }
}
