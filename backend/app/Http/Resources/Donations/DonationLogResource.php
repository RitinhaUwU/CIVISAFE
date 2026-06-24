<?php

namespace App\Http\Resources\Donations;

use App\Models\Donations\DonationLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DonationLog */
class DonationLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'date' => $this->date,
            'name' => $this->name,
            'contact' => $this->contact,
            'email' => $this->email,
            'donor_type' => $this->donor_type,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'user_id' => $this->user_id,
            'user' => $this->user,

            'content' => $this->donationContent
        ];
    }
}
