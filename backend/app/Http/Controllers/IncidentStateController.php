<?php

namespace App\Http\Controllers;

use App\Http\Requests\EntityRequest;
use App\Http\Requests\IncidentStatesRequest;
use App\Http\Resources\EntityResource;
use App\Http\Resources\IncidentStateResource;
use App\Models\Entity;
use App\Models\IncidentState;
use Illuminate\Http\Request;

class IncidentStateController extends Controller
{
    public function index()
    {
        return IncidentStateResource::collection(IncidentState::all());
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
