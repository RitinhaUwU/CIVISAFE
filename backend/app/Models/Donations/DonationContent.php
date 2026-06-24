<?php

namespace App\Models\Donations;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DonationContent extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'donation_log_id',
        'donation_goods_types_id',
        'quantity',
        'good_id',
    ];

    public function donationLog(): BelongsTo
    {
        return $this->belongsTo(DonationLog::class);
    }

    public function donationGoodsTypes(): BelongsTo
    {
        return $this->belongsTo(DonationGoodsType::class);
    }
}
