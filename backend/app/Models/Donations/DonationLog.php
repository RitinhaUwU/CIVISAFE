<?php

namespace App\Models\Donations;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class DonationLog extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'date',
        'name',
        'contact',
        'email',
        'donor_type',
        'user_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function donationContent(): HasMany
    {
        return $this->hasMany(DonationContent::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'date',
                'name',
                'contact',
                'email',
                'donor_type'
            ])
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
