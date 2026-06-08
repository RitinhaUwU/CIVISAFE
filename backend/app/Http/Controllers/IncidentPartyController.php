<?php

namespace App\Http\Controllers;

use App\Http\Requests\IncidentPartyRequest;
use App\Http\Resources\IncidentPartyResource;
use App\Models\Incident;
use App\Models\IncidentParty;
use App\Models\IncidentPCO;

class IncidentPartyController extends Controller
{
    public function index($incidentId)
    {
        return IncidentPartyResource::collection(
            IncidentParty::with(['entity'])
                ->where('incident_id', $incidentId)
                ->orderBy('id', 'asc')
                ->get()
        );
    }

    public function store(IncidentPartyRequest $request, $incidentId)
    {
        $data = $request->validated();
        $data['incident_id'] = $incidentId;

        return new IncidentPartyResource(IncidentParty::create($data));
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

    public function destroy($incidentId, IncidentParty $party)
    {
        if ($party->incident_id !== (int) $incidentId) {
            abort(404);
        }

        $party->delete();
        return response()->json();
    }
}
