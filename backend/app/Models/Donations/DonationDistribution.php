<?php

namespace App\Models\Donations;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DonationDistribution extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'contact',
        'obs',
        'user_id',
    ];

    public function distributionContent(): HasMany
    {
        return $this->hasMany(DistributionContent::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
