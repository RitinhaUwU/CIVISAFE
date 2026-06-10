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

        $conflict = IncidentPCO::where('incident_id', $incidentId)
            ->where('function_pco', $data['function_pco'])
            ->whereNull('end_pco_datetime')
            ->exists();

        if ($conflict) {
            return response()->json(['message' => 'Já existe uma função ativa com este cargo no PCO.'], 422);
        }

        $pco = IncidentPCO::create($data);

        return new IncidentPCOResource($pco);
    }

    public function show(IncidentPCO $pco)
    {
        return new IncidentPCOResource($pco -> load([
            'incidentPCO'
        ]));
    }

    public function update(IncidentPCORequest $request,  $incidentId, IncidentPCO $pco)
    {
        $data = $request->validated();

        if ($pco->function_pco !== $data['function_pco']) {

            $conflict = IncidentPCO::where('incident_id', $incidentId)
                ->where('function_pco', $data['function_pco'])
                ->whereNull('end_pco_datetime')
                ->where('id', '!=', $pco->id)
                ->exists();

            if ($conflict) {
                return response()->json(['message' => 'Já existe uma função ativa com este cargo no PCO.'], 422);
            }
        }

        $pco->update($data);

        return new IncidentPCOResource($pco);
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
