<?php

namespace Database\Factories\Donations;

use App\Models\Donations\DistributionContent;
use App\Models\Donations\DonationGoodsType;
use Illuminate\Database\Eloquent\Factories\Factory;

class DistributionContentFactory extends Factory
{
    protected $model = DistributionContent::class;

    public function definition(): array
    {
        return [
            'quantity' => $this->faker->randomNumber(1),

            //'donation_log_id' => DonationLog::inRandomOrder()->first()->id,
            'donation_goods_type_id' => DonationGoodsType::inRandomOrder()->first()->id,
        ];
    }
}
