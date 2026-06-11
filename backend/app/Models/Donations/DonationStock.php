<?php

namespace App\Models\Donations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DonationStock extends Model
{
    protected $fillable = [
        'donation_goods_type_id',
        'stock',
    ];

    public function donationGoodsType(): BelongsTo
    {
        return $this->belongsTo(DonationGoodsType::class);
    }
}
