<?php

namespace Database\Factories;

use App\Models\Incident;
use App\Models\IncidentPriority;
use App\Models\IncidentState;
use App\Models\IncidentType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class IncidentFactory extends Factory
{
    protected $model = Incident::class;

    public function definition(): array
    {
        $is_major = $this->faker->boolean(20);
        $major_id = $is_major ? Incident::inRandomOrder()->value('id') : null;

        $start = $this->faker->dateTime('-1 week')->format('Y-m-d H:i:s');
        $end = $this->faker->dateTimeBetween($start, 'yesterday')->format('Y-m-d H:i:s');

        return [
            'identifier' => date('Y') . "/" . $this->faker->unique()->randomNumber(4),
            'start_datetime' => $start,
            'end_datetime' => $end,
            'coordinates' => implode(", ", $this->faker->localCoordinates()),
            'common_place' => $this->faker->words(2, true),
            'address' => $this->faker->streetAddress(),
            'parish' => $this->faker->city(),
            'municipality' => $this->faker->city(),
            'district' => $this->faker->city(),
            'coordinates_pco' => implode(", ", $this->faker->localCoordinates()),
            'name_pco' => $this->faker->name(),
            'is_major' => $is_major,
            'alert_source_relationship' => $this->faker->randomElement(['Proprietário', 'Vizinho', 'Empresa', 'Proteção Civil']),
            'alert_source_name' => $this->faker->name(),
            'alert_source_contact' => $this->faker->phoneNumber(),
            'obs' => $this->faker->text(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'incident_type_id' => IncidentType::inRandomOrder()->first()->id,
            'incident_state_id' => IncidentState::inRandomOrder()->first()->id,
            'user_id' => User::inRandomOrder()->first()->id,
            'incident_priority_id' => IncidentPriority::inRandomOrder()->first()->id,
            'incident_id' => $major_id,
        ];
    }
}
