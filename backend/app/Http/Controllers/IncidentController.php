<?php

namespace App\Http\Controllers;

use App\Http\Requests\IncidentRequest;
use App\Http\Resources\IncidentResource;
use App\Models\Incident;
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
            ->orderBy('id', 'asc')
            ->paginate($request->input('per_page', 10))
            ->appends($request->query());

        return IncidentResource::collection($incidents);
    }

    public function store(IncidentRequest $request)
    {
        return DB::transaction(function () use ($request) {

            $data = $request->validated();
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
