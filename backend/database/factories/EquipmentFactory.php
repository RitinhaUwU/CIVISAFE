<?php

namespace Database\Factories;

use App\Models\Entity;
use App\Models\Equipment;
use App\Models\EquipmentType;
use App\Models\Facility;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class EquipmentFactory extends Factory
{
    protected $model = Equipment::class;

    public function definition(): array
    {
        $equipmentType = EquipmentType::inRandomOrder()->first();

        $custom_fields = [];

        foreach (json_decode($equipmentType->custom_fields) as $item) {
            $custom_fields[$item] = $this->faker->word();
        }

        return [
            'name' => $this->faker->word(),
            'custom_fields_data' => $custom_fields,
            'image' => null,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'equipment_type_id' => $equipmentType->id,
            'entity_id' => Entity::inRandomOrder()->first()->id,
            'facility_id' => Facility::inRandomOrder()->first()->id,
        ];
    }
}
