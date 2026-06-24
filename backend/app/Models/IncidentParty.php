<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class IncidentParty extends Model
{
    use SoftDeletes, HasFactory, LogsActivity;

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    protected $fillable = [
        'incident_id',
        'entity_id',
        'vehicle_count',
        'human_count',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'entity_id',
                'vehicle_count',
                'human_count',
            ])
            ->logOnlyDirty()
            ->useLogName('parties')
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => 'registou uma equipa',
                'updated' => 'atualizou uma equipa',
                'deleted' => 'eliminou uma equipa',
                default   => $eventName,
            });
    }
}
