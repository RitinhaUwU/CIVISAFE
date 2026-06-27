<?php

namespace App\Http\Resources\Donations;

use App\Models\Donations\DonationStock;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DonationStock */
class DonationStockResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'type_id' => $this->donation_goods_type_id,
            'type_name' => $this->donationGoodsType->name,
            'stock' => $this->stock,
        ];
    }
}
