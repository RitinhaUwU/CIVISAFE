<?php

namespace App\Http\Controllers;

use App\Http\Requests\IncidentTypeRequest;
use App\Http\Resources\IncidentTypeResource;
use App\Models\IncidentType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class IncidentTypeController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:INCIDENT_TYPES_LIST')->only(['index']);
        $this->middleware('permission:INCIDENT_TYPES_UPLOAD')->only(['store']);
    }

    public function index(Request $request)
    {
        $request->validate([
            'per_page' => 'sometimes|integer',
            'search' => 'nullable|string'
        ]);

        $types = QueryBuilder::for(IncidentType::class)
            ->allowedFilters(
                AllowedFilter::callback('search', function (Builder $query, $value) {
                    $query->where(function (Builder $q) use ($value) {
                        $q->where('code', 'ILIKE', "%{$value}%")
                            ->orWhere('species', 'ILIKE', "%{$value}%")
                            ->orWhere('type', 'ILIKE', "%{$value}%");
                    });
                }),
            )
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
}
