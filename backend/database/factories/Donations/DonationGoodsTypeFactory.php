<?php

namespace Database\Factories\Donations;

use App\Models\Donations\DonationGoodsType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class DonationGoodsTypeFactory extends Factory
{
    protected $model = DonationGoodsType::class;

    public function definition(): array
    {
        $is_countable = $this->faker->boolean(75);

        return [
            'name' => $this->faker->words(3, true),
            'is_type_countable' => $is_countable,
            'unit' => $is_countable ? $this->faker->randomElement(['liters', 'kilos', 'units', 'linear_meters', 'squared_meters']) : null,
            'danger_level' => $this->faker->boolean() ? $this->faker->randomNumber(2) : null,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
