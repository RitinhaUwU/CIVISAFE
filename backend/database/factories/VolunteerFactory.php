<?php

namespace Database\Factories;

use App\Models\Incident;
use App\Models\Volunteer;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class
VolunteerFactory extends Factory
{
    protected $model = Volunteer::class;

    public function definition(): array
    {
        //Codigo para verificar se a classificação=single para num elementos ser 1 como default

        $start = $this->faker->dateTime('-1 week')->format('Y-m-d H:i:s');
        $end = $this->faker->dateTimeBetween($start, 'yesterday')->format('Y-m-d H:i:s');

        $classification = $this->faker->randomElement(['single', 'org', 'misc']);

        return [
            'name' => $this->faker->name(),
            'contact' => $this->faker->phoneNumber(),
            'email' => $this->faker->unique()->safeEmail(),
            'classification' => $classification,
            'num_elements' => $classification === 'single' ? 1 : $this->faker->numberBetween(2, 20),
            'mission' => $this->faker->text(),
            'team_identification' => $this->faker->text(),
            'has_accommodation' => $this->faker->boolean(),
            'location' => $this->faker->address(),
            'start_datetime' => $start,
            'end_datetime' => $end,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'incident_id' => Incident::inRandomOrder()->first()->id,
        ];
    }
}
