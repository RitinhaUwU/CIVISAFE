<?php

namespace Database\Factories\Donations;

use App\Models\Donations\DonationContent;
use App\Models\Donations\DonationGoodsType;
use App\Models\Donations\DonationLog;
use Illuminate\Database\Eloquent\Factories\Factory;

class DonationContentFactory extends Factory
{
    protected $model = DonationContent::class;

    public function definition(): array
    {
        return [
            'quantity' => $this->faker->randomNumber(2),

            'donation_log_id' => DonationLog::inRandomOrder()->first()->id,
            'donation_goods_types_id' => DonationGoodsType::inRandomOrder()->first()->id,
        ];
    }
}
