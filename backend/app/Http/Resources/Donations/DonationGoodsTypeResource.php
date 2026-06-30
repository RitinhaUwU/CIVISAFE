<?php

namespace App\Http\Resources\Donations;

use App\Models\Donations\DonationGoodsType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DonationGoodsType */
class DonationGoodsTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'is_type_countable' => $this->is_type_countable,
            'unit' => $this->unit,
            'danger_level' => $this->danger_level,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
