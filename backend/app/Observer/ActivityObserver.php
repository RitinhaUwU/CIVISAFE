<?php

namespace App\Observer;

use App\Events\Incidents\TimelineEvent;
use App\Models\Incident;
use App\Models\IncidentParty;
use App\Models\IncidentPCO;
use App\Models\TimelineComment;
use Spatie\Activitylog\Models\Activity;

class ActivityObserver
{
    protected array $classes = [
        Incident::class,
        IncidentPCO::class,
        IncidentParty::class,
        TimelineComment::class,
    ];

    public function created(Activity $activity): void
    {
        if (in_array($activity->subject_type, $this->classes, true)) {
            if($activity->subject_type == Incident::class) {
                $incidentID = $activity->subject_id;
            }
            else
            {
                $incidentID = $activity->subject_type::find($activity->subject_id)->incident_id;
            }
            broadcast(new TimelineEvent($activity, $incidentID));
        }
    }
}
