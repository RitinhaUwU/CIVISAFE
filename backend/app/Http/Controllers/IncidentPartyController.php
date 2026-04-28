<?php

namespace App\Http\Controllers;

use App\Http\Requests\IncidentPartyRequest;
use App\Http\Resources\IncidentPartyResource;
use App\Models\IncidentParty;
use Illuminate\Routing\Controller;

class IncidentPartyController extends Controller
{
    public function index()
    {
        return IncidentPartyResource::collection(IncidentParty::all());
    }

    public function store(IncidentPartyRequest $request)
    {
        return new IncidentPartyResource(IncidentParty::create($request->validated()));
    }

    public function show(IncidentParty $incidentResource)
    {
        return new IncidentPartyResource($incidentResource);
    }

    public function update(IncidentPartyRequest $request, IncidentParty $incidentResource)
    {
        $incidentResource->update($request->validated());

        return new IncidentPartyResource($incidentResource);
    }

    public function destroy(IncidentParty $incidentResource)
    {
        $incidentResource->delete();

        return response()->json();
    }
}
