<?php

namespace App\Http\Controllers;

use App\Http\Resources\IncidentStateResource;
use App\Models\IncidentState;
use Illuminate\Http\Request;

class IncidentStateController extends Controller
{
    public function index()
    {
        return IncidentStateResource::collection(IncidentState::all());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required'],
            'description' => ['required'],
            'rgb_color' => ['required'],
            'terminates_incident' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        return new IncidentStateResource(IncidentState::create($data));
    }

    public function show(IncidentState $incidentState)
    {
        return new IncidentStateResource($incidentState);
    }

    public function update(Request $request, IncidentState $incidentState)
    {
        $data = $request->validate([
            'name' => ['required'],
            'description' => ['required'],
            'rgb_color' => ['required'],
            'terminates_incident' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        $incidentState->update($data);

        return new IncidentStateResource($incidentState);
    }

    public function destroy(IncidentState $incidentState)
    {
        $incidentState->delete();

        return response()->json();
    }
}
