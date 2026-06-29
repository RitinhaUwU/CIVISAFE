<?php

namespace App\Http\Controllers;

use App\Http\Requests\IncidentStatesRequest;
use App\Http\Resources\IncidentStateResource;
use App\Models\IncidentState;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class IncidentStateController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:INCIDENT_STATES_LIST')->only(['index', 'show']);
        $this->middleware('permission:INCIDENT_STATES_CREATE')->only(['store']);
        $this->middleware('permission:INCIDENT_STATES_UPDATE')->only(['update']);
        $this->middleware('permission:INCIDENT_STATES_DELETE')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $types = QueryBuilder::for(IncidentState::class)
            ->allowedFilters(
                AllowedFilter::callback('search', function (Builder $query, $value) {
                    $query->where('name', 'ILIKE', "%{$value}%");
                }),
                AllowedFilter::callback('status', function (Builder $query, $value) {
                    if ($value === 'all' || $value === null) {
                        return;
                    }
                    $query->where('is_active', $value);
                }),
                AllowedFilter::callback('terminates', function (Builder $query, $value) {
                    if ($value === 'all' || $value === null) {
                        return;
                    }
                    $query->where('terminates_incident', $value);
                }),
            )
            ->orderBy('id')
            ->cursorPaginate($request->input('per_page', 10))
            ->appends($request->query());

        return IncidentStateResource::collection($types);
    }

    public function store(IncidentStatesRequest $request)
    {
        return new IncidentStateResource(IncidentState::create($request->validated()));
    }

    public function show(IncidentState $incidentState)
    {
        return new IncidentStateResource($incidentState);
    }

    public function update(IncidentStatesRequest $request, IncidentState $incidentState)
    {
        $incidentState->update($request->validated());

        return new IncidentStateResource($incidentState);
    }

    public function destroy(IncidentState $incidentState)
    {
        $incidentState->delete();

        return response()->json();
    }
}
