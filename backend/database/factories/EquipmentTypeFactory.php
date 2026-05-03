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
            $fields[] = $this->faker->word();
        }

        return [
            'name' => $this->faker->name(),
            'custom_fields' => json_encode($fields),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
