<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Incident extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

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
        return $this->belongsTo(Incident::class, 'incident_id', 'id');
    }

    public function childrenIncidents(): HasMany
    {
        return $this->hasMany(Incident::class, 'incident_id', 'id');
    }

    public function parties(): HasMany
    {
        return $this->hasMany(IncidentParty::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TimelineComment::class);
    }

    protected $with = [
        'incidentType',
        'incidentState',
        'incidentPriority',
        'user',
        'parties',
        'comments',
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
        'is_major',
        'alert_source_relationship',
        'alert_source_name',
        'alert_source_contact',
        'obs',
        'incident_id',
        'coordinates_pco',
        'name_pco'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'identifier',
                'incident_type_id',
                'incident_state_id',
                'incident_priority_id',
                'start_datetime',
                'end_datetime',
                'coordinates',
                'common_place',
                'address',
                'parish',
                'municipality',
                'district',
                'is_major',
                'alert_source_relationship',
                'alert_source_name',
                'alert_source_contact',
                'obs',
                'coordinates_pco',
                'name_pco',
            ])
            ->logOnlyDirty()
            ->useLogName('incidents')
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => 'criou uma ocorrência',
                'updated' => 'atualizou uma ocorrência',
                'deleted' => 'eliminou uma ocorrência',
                default   => $eventName,
            });
    }
}
