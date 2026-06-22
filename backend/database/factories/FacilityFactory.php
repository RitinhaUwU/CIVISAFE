<?php

namespace Database\Factories;

use App\Models\Facility;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class FacilityFactory extends Factory
{
    protected $model = Facility::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'address' => $this->faker->address(),
            'email' => $this->faker->unique()->safeEmail(),
            'contact' => $this->faker->phoneNumber(),
            'image' => null,
            'description' => $this->faker->text(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
