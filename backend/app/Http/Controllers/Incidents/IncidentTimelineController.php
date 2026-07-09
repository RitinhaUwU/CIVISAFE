<?php

namespace App\Http\Controllers\Incidents;

use App\Http\Controllers\Controller;
use App\Http\Helpers\ActivityHelper;
use App\Models\Incident;
use App\Models\IncidentParty;
use App\Models\IncidentPCO;
use Spatie\Activitylog\Models\Activity;

class IncidentTimelineController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:INCIDENTS_LIST')->only(['index']);
    }
    public function index(Incident $incident)
    {
        $logs = Activity::query()
            ->where(function ($q) use ($incident) {
                $q->where('subject_type', Incident::class)->where('subject_id', $incident->id);
            })
            ->orWhere(function ($q) use ($incident) {
                $q->where('subject_type', IncidentPCO::class)->whereIn('subject_id', function ($sub) use ($incident) {
                    $sub->select('id')->from('incident_pcos')->where('incident_id', $incident->id);
                });
            })
            ->orWhere(function ($q) use ($incident) {
                $q->where('subject_type', IncidentParty::class)->whereIn('subject_id', function ($sub) use ($incident) {
                    $sub->select('id')->from('incident_parties')->where('incident_id', $incident->id);
                });
            })
            ->with('causer')
            ->get()
            ->map(fn($a) => [
                'id'         => $a->id,
                'type'       => 'log',
                'module'     => $a->log_name,
                'event'      => $a->description,
                'user'       => $a->causer?->name,
                'date'       => $a->created_at->toISOString(),
                'changes'    => ActivityHelper::transformActivityValues($a->attribute_changes['attributes'] ?? []),
                'old_values' => ActivityHelper::transformActivityValues($a->attribute_changes['old'] ?? []),
                'body'       => null,
                'comment_id' => null,
            ]);

        $comments = $incident->comments()
            ->with('user')
            ->get()
            ->map(fn($comment) => [
                'id'         => $comment->id,
                'type'       => 'comment',
                'module'     => 'comments',
                'event'      => 'acrescentou uma entrada',
                'user'       => $comment->user?->name,
                'body'       => $comment->body,
                'date'       => $comment->datetime->toISOString(),
                'changes'    => null,
                'old_values' => null,
                'comment_id' => $comment->id,
            ]);

        $timeline = $logs->concat($comments)->sortBy([['date', 'desc'], ['id', 'desc'],])->values();

        return response()->json($timeline);
    }
}
