<?php

namespace Database\Factories;

use App\Models\Entity;
use App\Models\Incident;
use App\Models\IncidentEntitiesMapping;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class IncidentEntitiesMappingFactory extends Factory
{
    protected $model = IncidentEntitiesMapping::class;

    public function definition(): array
    {
        return [
            'human_resource_amount' => $this->faker->randomNumber(2),
            'vehicle_amount' => $this->faker->randomNumber(2),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'incident_id' => Incident::inRandomOrder()->first()->id,
            'entity_id' => Entity::inRandomOrder()->first()->id,
        ];
    }
}
