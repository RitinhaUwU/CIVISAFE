<?php

namespace App\Http\Controllers\Incidents;

use App\Http\Controllers\Controller;
use App\Http\Helpers\ActivityHelper;
use App\Models\Incident;
use App\Models\IncidentParty;
use App\Models\IncidentPCO;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

class IncidentTimelineController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:INCIDENTS_LIST')->only(['index']);
    }

    public function index(Request $request, Incident $incident)
    {
        $validated = $request->validate([
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $logsQuery = DB::table('activity_log')
            ->select(
                'id',
                DB::raw("'log' as type"),
                'log_name as module',
                'event as event',
                'description as description',
                'causer_id as user_id',
                'created_at as date',
                'attribute_changes',
                DB::raw("NULL as body"),
                DB::raw("NULL as comment_id")
            )
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
            });

        $commentsQuery = DB::table('timeline_comments')
            ->select(
                'id',
                DB::raw("'comment' as type"),
                DB::raw("'comments' as module"),
                DB::raw("NULL as event"),
                DB::raw("'acrescentou uma entrada' as description"),
                'user_id',
                'datetime as date',
                DB::raw("NULL as attribute_changes"),
                'body',
                'id as comment_id'
            )
            ->where('incident_id', $incident->id);

        $unionQuery = $logsQuery->unionAll($commentsQuery);

        $timeline = DB::query()
            ->fromSub($unionQuery, 'timeline_data') // A net diz que isto é obrigatório para a tabela temporária
            ->orderByDesc('date')
            ->orderByDesc('type')
            ->orderByDesc('id')
            ->cursorPaginate($request->input('per_page', 10));

        $userIds = collect($timeline->items())->pluck('user_id')->filter()->unique();
        $users = User::whereIn('id', $userIds)->get()->keyBy('id');

        $timeline->through(function ($item) use ($users) {
            $attribute_changes = json_decode($item->attribute_changes, true);

            return [
                'id' => $item->id, // Obrigatório para o cursor
                'type' => $item->type, // Obrigatório para o cursor
                'date' => Carbon::parse($item->date)->toISOString(), // Obrigatório para o cursor

                'module' => $item->module,
                'event' => $item->event,
                'action' => $item->description,
                'username' => $item->user_id ? $users->get($item->user_id)?->name : null,
                'changes' => $item->type === 'log' ? ActivityHelper::transformActivityValues($attribute_changes['attributes'] ?? []) : null,
                'old_values' => $item->type === 'log' ? ActivityHelper::transformActivityValues($attribute_changes['old'] ?? []) : null,
                'body' => $item->body,
                'comment_id' => $item->comment_id,
                'log_id' => $item->type === 'log' ? $item->id : null,
            ];
        });

        return response()->json($timeline);
    }
}
