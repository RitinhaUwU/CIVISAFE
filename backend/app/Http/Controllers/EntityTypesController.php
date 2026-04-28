<?php

namespace App\Http\Controllers;

use App\Http\Requests\EntityTypes\EntityTypeRequest;
use App\Http\Resources\EntityTypesResource;
use App\Models\EntityType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class EntityTypesController extends Controller
{
    public function index(Request $request){
        $request->validate([
            'per_page' => 'sometimes|integer',
        ]);

        $types = QueryBuilder::for(EntityType::class)
            ->allowedFilters(
                AllowedFilter::callback('search', function (Builder $query, $value) {
                    $query->where('name', 'ILIKE', "%{$value}%");
                }),
            )
            ->paginate($request->input('per_page', 10))
            ->appends($request->query());

        return EntityTypesResource::collection($types);
    }

    public function store(EntityTypeRequest $request)
    {
        return new EntityTypesResource(EntityType::create($request->validated()));
    }

    public function show(EntityType $entityType)
    {
        return new EntityTypesResource($entityType);
    }

    public function update(EntityTypeRequest $request, EntityType $entityType)
    {
        $entityType->update($request->validated());

        return new EntityTypesResource($entityType);
    }

    public function destroy(EntityType $entityType)
    {
        $entityType->delete();

        return response()->json();
    }
}
