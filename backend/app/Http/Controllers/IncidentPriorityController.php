<?php

namespace App\Http\Controllers;

use App\Http\Requests\IncidentPriorityRequest;
use App\Http\Resources\IncidentPriorityResource;
use App\Models\IncidentPriority;

class IncidentPriorityController extends Controller
{
    public function index()
    {
        return IncidentPriorityResource::collection(IncidentPriority::all());
    }

    public function store(IncidentPriorityRequest $request)
    {
        return new IncidentPriorityResource(IncidentPriority::create($request->validated()));
    }

    public function show(IncidentPriority $incidentPriority)
    {
        return new IncidentPriorityResource($incidentPriority);
    }

    public function update(IncidentPriorityRequest $request, IncidentPriority $incidentPriority)
    {
        $incidentPriority->update($request->validated());

        return new IncidentPriorityResource($incidentPriority);
    }

    public function destroy(IncidentPriority $incidentPriority)
    {
        $incidentPriority->delete();

        return response()->json();
    }
}
