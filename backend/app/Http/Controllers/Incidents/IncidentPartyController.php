<?php

namespace App\Http\Controllers\Incidents;

use App\Http\Controllers\Controller;
use App\Http\Requests\Incidents\IncidentPartyRequest;
use App\Http\Resources\Incidents\IncidentPartyResource;
use App\Models\IncidentParty;

class IncidentPartyController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:INCIDENTS_LIST')->only(['index', 'show']);
        $this->middleware('permission:INCIDENTS_UPDATE')->only(['store', 'update']);
    }

    public function index($incidentId)
    {
        $parties = IncidentParty::with(['entity'])
            ->where('incident_id', $incidentId)
            ->orderBy('id', 'asc')
            ->get();

        return IncidentPartyResource::collection($parties)->additional([
            'meta' => [
                'total_vehicles' => $parties->sum('vehicle_count'),
                'total_humans' => $parties->sum('human_count'),
            ]
        ]);
    }

    public function store(IncidentPartyRequest $request, $incidentId)
    {
        $data = $request->validated();
        $data['incident_id'] = $incidentId;

        $existing = IncidentParty::where('incident_id', $incidentId)->where('entity_id', $data['entity_id'])->first();

        $merged = (bool) $existing;

        if ($existing) {
            $existing->update([
                'vehicle_count' => $existing->vehicle_count + ($data['vehicle_count'] ?? 0),
                'human_count' => $existing->human_count + ($data['human_count'] ?? 0),
            ]);
            $party = $existing;
        } else {
            $party = IncidentParty::create($data);
        }

        return (new IncidentPartyResource($party)->additional(['meta' => ['merged' => $merged]]));
    }

    public function show(IncidentParty $incidentParty)
    {
        return new IncidentPartyResource($incidentParty -> load([
            'incident',
            'entity'
        ]));
    }

    public function update(IncidentPartyRequest $request,  $incidentId, IncidentParty $party)
    {
        $party->update($request->validated());

        return new IncidentPartyResource($party);
    }
}
