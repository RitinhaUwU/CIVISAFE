<?php

namespace Database\Factories\Donations;

use App\Enums\PermissionsEnum;
use App\Models\Donations\DonationLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class DonationLogFactory extends Factory
{
    protected $model = DonationLog::class;

    public function definition(): array
    {
        $type = $this->faker->randomElement(['single', 'org', 'misc']);
        return [
            'name' => $type === 'single' ? $this->faker->name() : $this->faker->company(),
            'contact' => $this->faker->phoneNumber(),
            'email' => $this->faker->unique()->safeEmail(),
            'donor_type' => $this->faker->randomElement(['single', 'org', 'misc']),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'user_id' => User::permission(PermissionsEnum::DONATION_LOG_CREATE)->inRandomOrder()->first()->id,
        ];
    }
}
