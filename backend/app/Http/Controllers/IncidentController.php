<?php

namespace App\Http\Controllers;

use App\Http\Requests\IncidentRequest;
use App\Http\Resources\IncidentResource;
use App\Models\Incident;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class IncidentController extends Controller
{
    public function index(Request $request)
    {
        $incidents = QueryBuilder::for(Incident::class)
            ->with([
                'incidentType',
                'incidentState',
                'incidentPriority',
                'parentIncident',
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
                })
            )
            ->paginate($request->input('per_page', 10))
            ->appends($request->query());

        return IncidentResource::collection($incidents);
    }

    public function store(IncidentRequest $request)
    {
        return new IncidentResource(Incident::create($request->validated()));
    }

    public function show(Incident $incident)
    {
        return new IncidentResource($incident->load([
            'incidentType',
            'incidentState',
            'incidentPriority',
            'resources',
            'parentIncident',
            'user',
        ]));
    }

    public function update(IncidentRequest $request, Incident $incident)
    {
        $incident->update($request->validated());

        return new IncidentResource($incident);
    }

    public function destroy(Incident $incident)
    {
        $incident->delete();

        return response()->json();
    }
}
