<?php

namespace App\Http\Controllers\Incidents;

use App\Http\Controllers\Controller;
use App\Http\Requests\Incidents\IncidentRequest;
use App\Http\Resources\Incidents\IncidentResource;
use App\Models\Incident;
use App\Models\IncidentState;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class IncidentController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:INCIDENTS_LIST')->only(['index', 'show']);
        $this->middleware('permission:INCIDENTS_CREATE')->only(['store']);
        $this->middleware('permission:INCIDENTS_UPDATE')->only(['update']);
        $this->middleware('permission:INCIDENTS_DELETE')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $incidents = QueryBuilder::for(Incident::class)
            ->with([
                'incidentType',
                'incidentState',
                'incidentPriority',
                'parties',
                'parentIncident',
                'childrenIncidents',
                'user',
            ])
            ->allowedFilters(
                AllowedFilter::callback('search', function (Builder $query, $value) {
                    $query->where('identifier', 'ILIKE', "%{$value}%");
                }),
                AllowedFilter::callback('state', function (Builder $query, $value) {
                    if ($value === 'all' || !$value) return;
                    $query->where('incident_state_id', $value);
                }),
                AllowedFilter::callback('priority', function (Builder $query, $value) {
                    if ($value === 'all' || !$value) return;
                    $query->where('incident_priority_id', $value);
                }),
                AllowedFilter::callback('is_major', function (Builder $query, $value) {
                    if ($value === 'all' || $value === null) return;
                    $query->where('is_major', $value);
                }),
            )
            ->orderBy('id')
            ->cursorPaginate($request->input('per_page', 10))
            ->appends($request->query());

        return IncidentResource::collection($incidents);
    }

    private function applyEndDatetimeRule(array &$data): void
    {
        if (!isset($data['incident_state_id'])) {
            return;
        }

        $state = IncidentState::find($data['incident_state_id']);

        $data['end_datetime'] = $state?->terminates_incident ? now()->toIso8601String() : null;
    }

    public function store(IncidentRequest $request)
    {
        return DB::transaction(function () use ($request) {

            $data = $request->validated();
            $this->applyEndDatetimeRule($data);
            $children = $data['children_incidents'] ?? [];

            unset($data['children_incidents']);

            $incident = Incident::create($data);

            if ($incident->is_major && !empty($children)) {
                Incident::whereIn('id', $children)->update(['incident_id' => $incident->id]);
            }

            return new IncidentResource(
                $incident->fresh([
                    'incidentType',
                    'incidentState',
                    'incidentPriority',
                    'parties',
                    'parentIncident',
                    'childrenIncidents',
                    'user',
                ])
            )->response()->setStatusCode(201);
        });
    }

    public function show(Incident $incident)
    {
        return new IncidentResource($incident->load([
            'incidentType',
            'incidentState',
            'incidentPriority',
            'parties',
            'parentIncident',
            'childrenIncidents',
            'user',
        ]));
    }

    public function update(IncidentRequest $request, Incident $incident)
    {
        return DB::transaction(function () use ($request, $incident) {

            $data = $request->validated();
            $this->applyEndDatetimeRule($data);
            $children = $data['children_incidents'] ?? [];

            unset($data['children_incidents']);

            $incident->update($data);

            Incident::where('incident_id', $incident->id)->update(['incident_id' => null]);

            if ($incident->is_major && !empty($children)) {
                Incident::whereIn('id', $children)->update(['incident_id' => $incident->id]);
            }

            return new IncidentResource(
                $incident->fresh([
                    'incidentType',
                    'incidentState',
                    'incidentPriority',
                    'parties',
                    'parentIncident',
                    'childrenIncidents',
                    'user',
                ])
            );
        });
    }

    public function destroy(Incident $incident)
    {
        $incident->delete();

        return response()->json();
    }
}
