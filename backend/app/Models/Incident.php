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

    public function incidentType(): BelongsTo
    {
        return $this->belongsTo(IncidentType::class, 'incident_type_id')->withTrashed();
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

    public function childrenIncidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }

    public function resources(): HasMany
    {
        return $this->hasMany(IncidentParty::class);
    }

    protected $with = [
        'incidentType',
        'incidentState',
        'incidentPriority',
        'user',
        'resources',
        'parentIncident',
        'childrenIncidents'
    ];

    protected function casts(): array
    {
        return [
            'start_datetime' => 'datetime',
            'end_datetime' => 'datetime',
            'is_major' => 'boolean',
        ];
    }

    protected $fillable = [
        'identifier',
        'incident_type_id',
        'incident_state_id',
        'incident_priority_id',
        'user_id',
        'start_datetime',
        'end_datetime',
        'coordinates',
        'common_place',
        'address',
        'parish',
        'municipality',
        'district',
        'command_post',
        'is_major',
        'alert_source_relationship',
        'alert_source_name',
        'alert_source_contact',
        'obs',
        'incident_id',
    ];
}
