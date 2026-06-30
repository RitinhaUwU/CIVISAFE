<?php

namespace Database\Factories;

use App\Models\Donations\DonationAudit;
use App\Models\Donations\DonationGoodsType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class DonationAuditFactory extends Factory
{
    protected $model = DonationAudit::class;

    public function definition(): array
    {
        return [
            'adjustment_type' => $this->faker->randomElement(['add', 'remove']),
            'quantity' => $this->faker->randomNumber(1),
            'reason' => $this->faker->randomElement(['diffCorrection', 'brokenItem', 'lost', 'other']),
            'obs' => $this->faker->realText(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'donation_goods_type_id' => DonationGoodsType::inRandomOrder()->first()->id,
            'user_id' => User::role(['admin', 'module_donations'])->inRandomOrder()->first()->id,
        ];
    }
}
