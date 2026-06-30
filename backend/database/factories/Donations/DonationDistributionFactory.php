<?php

namespace Database\Factories\Donations;

use App\Enums\RolesEnum;
use App\Models\Donations\DonationDistribution;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class DonationDistributionFactory extends Factory
{
    protected $model = DonationDistribution::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'contact' => $this->faker->phoneNumber(),
            'obs' => $this->faker->text(),
            'user_id' => User::role([RolesEnum::ADMIN, RolesEnum::MODULE_DONATIONS])->inRandomOrder()->first(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
