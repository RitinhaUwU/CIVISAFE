<?php

namespace App\Models\Donations;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class DonationAudit extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'adjustment_type',
        'donation_goods_type_id',
        'quantity',
        'reason',
        'obs',
        'user_id',
    ];

    public function donationGoodsType(): BelongsTo
    {
        return $this->belongsTo(DonationGoodsType::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'adjustment_type',
                'donation_goods_type_id',
                'quantity',
                'reason',
                'obs',
            ])
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
