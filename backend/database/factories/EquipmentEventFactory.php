<?php

namespace Database\Factories;

use App\Models\Equipment;
use App\Models\EquipmentEvent;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class EquipmentEventFactory extends Factory
{
    protected $model = EquipmentEvent::class;

    public function definition(): array
    {
        return [
            'date' => Carbon::now(),
            'event' => $this->faker->word(),
            'description' => $this->faker->text(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'equipment_id' => Equipment::factory(),
        ];
    }
}
