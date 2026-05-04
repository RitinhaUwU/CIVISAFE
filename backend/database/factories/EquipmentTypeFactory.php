<?php

namespace Database\Factories;

use App\Models\EquipmentType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class EquipmentTypeFactory extends Factory
{
    protected $model = EquipmentType::class;

    public function definition(): array
    {
        $fields = [];

        for ($i = 0; $i < rand(1, 8); $i++) {
            $fieldConfig['name'] = $this->faker->word();
            $fieldConfig['description'] = $this->faker->text();
            $fieldConfig['type'] = $this->faker->randomElement(['text', 'number', 'boolean']);
            $fieldConfig['mandatory'] = $this->faker->boolean();
            array_push($fields, $fieldConfig);
        }

        return [
            'name' => $this->faker->words(2, true),
            'custom_fields' => json_encode($fields),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
