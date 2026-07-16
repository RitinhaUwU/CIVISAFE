<?php

namespace App\Events\Incidents;

use App\Http\Helpers\ActivityHelper;
use App\Models\TimelineComment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\Models\Activity;

class TimelineEvent implements ShouldBroadcast, ShouldQueue
{
    use SerializesModels;

    public string $queue = 'notifications';

    public Activity $activity;
    public int $incidentID;

    public function __construct(Activity $activity, int $incidentID)
    {
        $this->activity = $activity;
        $this->incidentID = $incidentID;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('Incident.' . $this->incidentID),
        ];
    }

    public function broadcastWith(): array
    {
        $isTypeLog = $this->activity->subject_type != TimelineComment::class;

        return [
            'id'         => $this->activity->id,
            'type'       => $isTypeLog ? 'log' : 'comment',
            'date'       => $isTypeLog ? Carbon::parse($this->activity->created_at)->toISOString() : $this->activity->attribute_changes['attributes']['datetime'],

            'module'     => $this->activity->log_name,
            'event'      => $this->activity->event,
            'action'     => $isTypeLog ? $this->activity->description : 'acrescentou uma entrada',
            'username'   => User::find($this->activity->causer_id)?->name,
            'changes'    => $isTypeLog ? ActivityHelper::transformActivityValues($this->activity->attribute_changes['attributes'] ?? []) : null,
            'old_values' => $isTypeLog ? ActivityHelper::transformActivityValues($this->activity->attribute_changes['old'] ?? []) : null,
            'body'       => $isTypeLog ? null : $this->activity->attribute_changes['attributes']['body'] ?? null,
            'comment_id' => $isTypeLog ? null : $this->activity->subject_id,
            'log_id'     => $isTypeLog ? $this->activity->id : null,
        ];
    }

    public function broadcastAs(): string
    {
        return 'timeline.event';
    }
}
