<?php

namespace App\Models\Donations;

use App\Events\StockUpdated;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DonationStock extends Model
{
    protected $primaryKey = 'donation_goods_type_id';

    protected $fillable = [
        'donation_goods_type_id',
        'stock',
    ];

    public function donationGoodsType(): BelongsTo
    {
        return $this->belongsTo(DonationGoodsType::class);
    }
}
