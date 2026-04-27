<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Volunteer extends Model
{
    use HasFactory, SoftDeletes;

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    protected $fillable = [
        'name',
        'contact',
        'email',
        'num_elements',
        'mission',
        'team_identification',
        'classification',
        'has_accommodation',
        'location',
        'has_meal',
        'meal_notes',
        'meal_location',
        'start_datetime',
        'end_datetime',
        'incident_id',
    ];

    protected function casts(): array
    {
        return [
            'has_accommodation' => 'boolean',
            'start_datetime' => 'datetime',
            'end_datetime' => 'datetime',
        ];
    }
}
