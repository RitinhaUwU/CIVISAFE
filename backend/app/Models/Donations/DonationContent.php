<?php

namespace App\Models\Donations;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class DonationContent extends Model
{
    use HasFactory, LogsActivity;

    public $timestamps = false;

    protected $fillable = [
        'donation_log_id',
        'donation_goods_types_id',
        'quantity'
    ];

    public function donationLog(): BelongsTo
    {
        return $this->belongsTo(DonationLog::class);
    }

    public function donationGoodsTypes(): BelongsTo
    {
        return $this->belongsTo(DonationGoodsType::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['donation_goods_types_id', 'quantity'])
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
