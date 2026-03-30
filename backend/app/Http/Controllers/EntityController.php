<?php

namespace App\Http\Controllers;

use App\Http\Requests\EntityRequest;
use App\Http\Resources\EntityResource;
use App\Models\Entity;

class EntityController extends Controller
{
    public function index()
    {
        return EntityResource::collection(Entity::paginate(15));
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
