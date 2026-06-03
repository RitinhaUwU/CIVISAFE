<?php

namespace App\Http\Controllers;

use App\Http\Requests\IncidentPCORequest;
use App\Http\Resources\IncidentPCOResource;
use App\Models\incidentPCO;
use Spatie\QueryBuilder\QueryBuilder;

class IncidentPCOController extends Controller
{
    public function index()
    {
        $incidentPCO = QueryBuilder::for(IncidentPCO::class)
            ->with([
                'incidentPCO',
            ])
            ->orderBy('id', 'desc');

        return IncidentPCOResource::collection($incidentPCO);
    }

    public function store(IncidentPCORequest $request)
    {
        return new IncidentPCOResource(IncidentPCO::create($request->validated()));
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

    public function destroy(IncidentPCO $incidentPCO)
    {
        $incidentPCO->delete();

        return response()->json();
    }
}
