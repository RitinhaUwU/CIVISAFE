<?php

namespace App\Models\Donations;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DonationLog extends Model
{
    use HasFactory;

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
}
