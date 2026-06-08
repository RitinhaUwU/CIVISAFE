<?php

namespace App\Http\Controllers;

use App\Http\Requests\IncidentPCORequest;
use App\Http\Resources\IncidentPCOResource;
use App\Models\IncidentPCO;

class IncidentPCOController extends Controller
{
    public function index($incidentId)
    {
        return IncidentPCOResource::collection(
            IncidentPCO::where('incident_id', $incidentId)
                ->orderBy('id', 'desc')
                ->get()
        );
    }

    public function store(IncidentPCORequest $request, $incidentId)
    {
        $data = $request->validated();
        $data['incident_id'] = $incidentId;

        return new IncidentPCOResource(IncidentPCO::create($data));
    }

    public function show(IncidentPCO $incidentPCO)
    {
        return new IncidentPCOResource($incidentPCO -> load([
            'incidentPCO'
        ]));
    }

    public function update(IncidentPCORequest $request, IncidentPCO $incidentPCO)
    {
        $incidentPCO->update($request->validated());

        return new IncidentPCOResource($incidentPCO);
    }

    public function destroy($incidentId, IncidentPCO $pco)
    {
        if ($pco->incident_id !== (int) $incidentId) {
            abort(404);
        }

        $pco->delete();
        return response()->json();
    }
}
