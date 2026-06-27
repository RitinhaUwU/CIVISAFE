<?php

namespace App\Models\Donations;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DistributionContent extends Model
{
    use HasFactory;

    protected $fillable = [
        'donation_log_id',
        'quantity',
        'donation_distribution_id',
        'donation_goods_type_id',
    ];

    public $timestamps = false;

    public function donationDistribution(): BelongsTo
    {
        return $this->belongsTo(DonationDistribution::class);
    }

    public function donationGoodsType(): BelongsTo
    {
        return $this->belongsTo(DonationGoodsType::class);
    }
}
