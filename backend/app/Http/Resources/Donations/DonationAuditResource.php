<?php

namespace App\Http\Resources\Donations;

use App\Models\Donations\DonationAudit;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DonationAudit */
class DonationAuditResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'adjustment_type' => $this->adjustment_type,
            'donation_goods_type_id' => $this->donationGoodsType()->withTrashed()->first()->only(['id', 'name', 'unit']),
            'quantity' => $this->quantity,
            'reason' => $this->reason,
            'obs' => $this->obs,
            'user_id' => $this->user()->withTrashed()->first()->only('id', 'name'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
