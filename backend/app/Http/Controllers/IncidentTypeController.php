<?php

namespace App\Http\Controllers;

use App\Http\Requests\IncidentTypeRequest;
use App\Http\Resources\IncidentTypeResource;
use App\Models\IncidentType;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;

class IncidentTypeController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['per_page' => 'sometimes|integer']);

        $types = QueryBuilder::for(IncidentType::class)
            ->allowedFilters('species')
            ->paginate($request->input('per_page', 10))
            ->appends($request->query());

        return IncidentTypeResource::collection($types);
    }

    public function store(IncidentTypeRequest $request)
    {
        return new IncidentTypeResource(IncidentType::create($request->validated())->fresh());
    }

    public function show(IncidentType $incidentType)
    {
        return new IncidentTypeResource($incidentType);
    }

    public function update(IncidentTypeRequest $request, IncidentType $incidentType)
    {
        $incidentType->update($request->validated());

        return new IncidentTypeResource($incidentType->fresh());
    }

    public function destroy(IncidentType $incidentType)
    {
        $incidentType->delete();

        return response()->json();
    }
}
