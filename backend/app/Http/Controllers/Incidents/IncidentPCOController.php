<?php

namespace App\Http\Controllers\Incidents;

use App\Http\Controllers\Controller;
use App\Http\Requests\Incidents\IncidentPCORequest;
use App\Http\Resources\Incidents\IncidentPCOResource;
use App\Models\IncidentPCO;

class IncidentPCOController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:INCIDENTS_LIST')->only(['index', 'show']);
        $this->middleware('permission:INCIDENTS_UPDATE')->only(['store', 'update']);
    }

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

        // Caso já exista uma função ativa (sem data de fim)
        $activeConflict = IncidentPCO::where('incident_id', $incidentId)
            ->where('function_pco', $data['function_pco'])
            ->whereNull('end_pco_datetime')
            ->first();

        if ($activeConflict) {
            return response()->json(['type' => 'active_conflict', 'message' => 'Já existe uma função ativa com este cargo no PCO.', ...$activeConflict->toArray()], 422);
        }

        // Caso haja sobreposição temporal com um registo
        $overlapConflict = IncidentPCO::where('incident_id', $incidentId)
            ->where('function_pco', $data['function_pco'])
            ->whereNotNull('end_pco_datetime')
            ->where('start_pco_datetime', '<', $data['end_pco_datetime'] ?? '9999-12-31')
            ->where('end_pco_datetime', '>', $data['start_pco_datetime'])
            ->first();

        if ($overlapConflict) {
            return response()->json(['type' => 'overlap', 'message' => 'Existe sobreposição temporal com outro registo desta função.'], 422);
        }

        $pco = IncidentPCO::create($data);

        return new IncidentPCOResource($pco);
    }

    public function show(IncidentPCO $pco)
    {
        return new IncidentPCOResource($pco->load([
            'incidentPCO'
        ]));
    }

    public function update(IncidentPCORequest $request, $incidentId, IncidentPCO $pco)
    {
        $data = $request->validated();

        // Caso já exista uma função ativa (sem data de fim)
        $activeConflict = IncidentPCO::where('incident_id', $incidentId)
            ->where('function_pco', $data['function_pco'])
            ->where('id', '!=', $pco->id)
            ->whereNull('end_pco_datetime')
            ->first();

        if ($activeConflict) {
            return response()->json(['type' => 'active_conflict', 'message' => 'Já existe uma função ativa com este cargo no PCO.'], 422);
        }

        // Caso haja sobreposição temporal com um registo
        $start = $data['start_pco_datetime'] ?? $pco->start_pco_datetime;
        $end = $data['end_pco_datetime'] ?? $pco->end_pco_datetime;

        $overlapConflict = IncidentPCO::where('incident_id', $incidentId)
            ->where('function_pco', $data['function_pco'])
            ->where('id', '!=', $pco->id)
            ->whereNotNull('end_pco_datetime')
            ->where('start_pco_datetime', '<', $end ?? '9999-12-31')
            ->where('end_pco_datetime', '>', $start)
            ->first();

        if ($overlapConflict) {
            return response()->json(['type' => 'overlap', 'message' => 'Existe sobreposição temporal com outro registo desta função.', ...$overlapConflict->toArray()], 422);
        }

        $pco->update($data);

        return new IncidentPCOResource($pco);
    }
}
