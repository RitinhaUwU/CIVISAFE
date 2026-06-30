<?php

namespace App\Http\Resources\Donations;

use App\Models\Donations\DonationDistribution;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DonationDistribution */
class DonationDistributionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'contact' => $this->contact,
            'obs' => $this->obs,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'user' => $this->user()->withTrashed()->first()?->only('id', 'name'),
            'goods' => $this->distributionContent,
        ];
    }
}
