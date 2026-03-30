<?php

namespace App\Http\Controllers;

use App\Http\Requests\IncidentRequest;
use App\Http\Resources\IncidentResource;
use App\Models\Incident;

class IncidentController extends Controller
{
    public function index()
    {
        return IncidentResource::collection(Incident::with([
            'category',
            'incidentState',
            'incidentPriority',
            'parentIncident',
        ])->paginate(15));
    }

    public function store(IncidentRequest $request)
    {
        return new IncidentResource(Incident::create($request->validated()));
    }

    public function show(Incident $incident)
    {
        return new IncidentResource($incident->load([
            'category',
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
