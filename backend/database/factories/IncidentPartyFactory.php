<?php

namespace Database\Factories;

use App\Models\Entity;
use App\Models\Incident;
use App\Models\IncidentParty;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class IncidentPartyFactory extends Factory
{
    protected $model = IncidentParty::class;

    public function definition(): array
    {
        return [
            'human_count' => $this->faker->randomNumber(2),
            'vehicle_count' => $this->faker->randomNumber(2),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
