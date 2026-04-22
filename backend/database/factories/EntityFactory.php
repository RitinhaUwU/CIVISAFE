<?php

namespace Database\Factories;

use App\Models\Entity;
use App\Models\EntityType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class EntityFactory extends Factory
{
    protected $model = Entity::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'description' => $this->faker->text(),
            'phone_contact' => $this->faker->phoneNumber(),
            'email_contact' => $this->faker->unique()->safeEmail(),
            'address' => $this->faker->address(),
            'logo' => $this->faker->word(),
            'poc_name' => $this->faker->name(),
            'poc_phone' => $this->faker->phoneNumber(),
            'poc_email' => $this->faker->unique()->safeEmail(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'entity_type_id' => EntityType::inRandomOrder()->first()->id,
        ];
    }
}
