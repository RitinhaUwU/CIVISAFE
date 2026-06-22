<?php

namespace Database\Factories;

use App\Models\Incident;
use App\Models\incidentPCO;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class IncidentPCOFactory extends Factory
{
    protected $model = incidentPCO::class;

    public function definition(): array
    {
        return [
            'function_pco' => $this->faker->randomElement(['COS', 'Oficial Operações', 'Oficial Logistica', 'Oficial  Planeamento', 'Oficial Operações Aérias', 'Adjunto Segurança', 'Adjunto Realações Públicas', 'Adjunto Ligação']),
            'resp_pco' => $this->faker->name(),
            'category_pco' => $this->faker->word(),
            'contact1_pco' => $this->faker->phoneNumber(),
            'contact2_pco' => $this->faker->phoneNumber(),
            'localization_pco' => $this->faker->city(),
            'rob_pco' => $this->faker->word(),
            'srp_pco' => $this->faker->word(),
            'activation_pco_datetime' => Carbon::now(),
            'start_pco_datetime' => Carbon::now(),
            'end_pco_datetime' => Carbon::now(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'incident_id' => Incident::inRandomOrder()->first()->id,
        ];
    }
}
