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
                    $query->where(function (Builder $q) use ($value) {
                        $q->where('identifier', 'ILIKE', "%{$value}%")
                            ->orWhereHas('incidentType', function (Builder $type) use ($value) {
                                $type->where('code', 'ILIKE', "%{$value}%")
                                    ->orWhere('species', 'ILIKE', "%{$value}%")
                                    ->orWhere('type', 'ILIKE', "%{$value}%");
                            });
                    });
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
                AllowedFilter::callback('terminates_incident', function (Builder $query, $value) {
                    if ($value === null) return;
                    $query->where('is_major', $value)->whereHas('incidentState', function (Builder $q) {
                        $q->where('terminates_incident', false);
                    });
                }),
                // https://laravel.com/docs/13.x/queries#whereraw-orwhereraw
                AllowedFilter::callback('bbox', function (Builder $query, $value) {
                    if (!is_array($value) || count($value) !== 4) return;

                    $coords = array_map(function ($v) {
                        if (!is_numeric($v)) return null;
                        return (float) $v;
                    }, $value);

                    if (in_array(null, $coords, true)) return;

                    [$west, $south, $east, $north] = $coords;

                    if ($south < -90 || $south > 90 || $north < -90 || $north > 90) return;
                    if ($west < -180 || $west > 180 || $east < -180 || $east > 180) return;

                    $query->whereRaw("split_part(coordinates, ',', 1)::float BETWEEN ? AND ?", [$south, $north])
                        ->whereRaw("split_part(coordinates, ',', 2)::float BETWEEN ? AND ?", [$west, $east]);
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

        if ($state?->terminates_incident && empty($data['end_datetime'])) {
            $data['end_datetime'] = now();
        }
    }

    // Dá preview do próximo identificador
    public function nextIdentifier()
    {
        return response()->json([
            'identifier' => Incident::nextIdentifier()
        ]);
    }

    public function store(IncidentRequest $request)
    {
        return DB::transaction(function () use ($request) {

            $data = $request->validated();
            $children = $data['children_incidents'] ?? [];
            unset($data['children_incidents']);

            $this->applyEndDatetimeRule($data);

            $incident = Incident::create($data);

            if ($incident->is_major && !empty($children)) {
                foreach (Incident::whereIn('id', $children)->get() as $child) {
                    $child->update(['incident_id' => $incident->id]);
                }
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

            $this->applyEndDatetimeRule($data);

            $incident->update($data);

            Incident::where('incident_id', $incident->id)->whereNotIn('id', $children)->get()->each(fn ($child) => $child->update(['incident_id' => null]));

            if ($incident->is_major && !empty($children)) {
                foreach (Incident::whereIn('id', $children)->get() as $child) {
                    $child->update(['incident_id' => $incident->id]);
                }
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
