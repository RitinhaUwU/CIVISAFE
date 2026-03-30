<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Incident extends Model
{
    use HasFactory, SoftDeletes;

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function incidentState(): BelongsTo
    {
        return $this->belongsTo(IncidentState::class);
    }

    public function incidentPriority(): BelongsTo
    {
        return $this->belongsTo(IncidentPriority::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parentIncident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    public function resources(): HasMany
    {
        return $this->hasMany(IncidentEntitiesMapping::class);
    }

    protected function casts(): array
    {
        return [
            'start_datetime' => 'datetime',
            'is_major' => 'boolean',
        ];
    }
}
