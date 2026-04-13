<?php

namespace App\Http\Controllers;

use App\Http\Requests\IncidentPriorityRequest;
use App\Http\Resources\IncidentPriorityResource;
use App\Models\IncidentPriority;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class IncidentPriorityController extends Controller
{
    public function index(Request $request)
    {
        $types = QueryBuilder::for(IncidentPriority::class)
            ->allowedFilters(
                AllowedFilter::callback('search', function (Builder $query, $value) {
                    $query->where(function (Builder $q) use ($value) {
                        $q->where('name', 'ILIKE', "%{$value}%")
                            ->orWhere('description', 'ILIKE', "%{$value}%");
                    });
                }),
                AllowedFilter::callback('status', function (Builder $query, $value) {
                    if ($value === 'all' || $value === null) {
                        return;
                    }

                    $query->where('is_active', $value);
                }),
            )
            ->paginate($request->input('per_page', 10))
            ->appends($request->query());

        return IncidentPriorityResource::collection($types);
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
