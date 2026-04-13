<?php

namespace App\Http\Controllers;

use App\Http\Requests\EntityRequest;
use App\Http\Resources\EntityResource;
use App\Http\Resources\IncidentTypeResource;
use App\Models\Entity;
use App\Models\IncidentType;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class EntityController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'per_page' => 'sometimes|integer',
        ]);

        $types = QueryBuilder::for(Entity::class)
            ->allowedFilters(
                AllowedFilter::callback('search', function (Builder $query, $value) {
                    $query->where('name', 'ILIKE', "%{$value}%");
                }),
            )
            ->paginate($request->input('per_page', 10))
            ->appends($request->query());

        return EntityResource::collection($types);
    }

    public function store(EntityRequest $request)
    {
        return new EntityResource(Entity::create($request->validated()));
    }

    public function show(Entity $entity)
    {
        return new EntityResource($entity);
    }

    public function update(EntityRequest $request, Entity $entity)
    {
        $entity->update($request->validated());

        return new EntityResource($entity);
    }

    public function destroy(Entity $entity)
    {
        $entity->delete();

        return response()->json();
    }
}
