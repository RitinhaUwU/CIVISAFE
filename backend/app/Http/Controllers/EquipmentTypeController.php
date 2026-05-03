<?php

namespace App\Http\Controllers;

use App\Http\Requests\EquipmentTypeRequest;
use App\Http\Resources\EquipmentTypeResource;
use App\Models\EquipmentType;

class EquipmentTypeController extends Controller
{
    public function index()
    {
        return EquipmentTypeResource::collection(EquipmentType::all());
    }

    public function store(EquipmentTypeRequest $request)
    {
        return new EquipmentTypeResource(EquipmentType::create($request->validated()));
    }

    public function show(EquipmentType $equipmentType)
    {
        return new EquipmentTypeResource($equipmentType);
    }

    public function update(EquipmentTypeRequest $request, EquipmentType $equipmentType)
    {
        $equipmentType->update($request->validated());

        return new EquipmentTypeResource($equipmentType);
    }

    public function destroy(EquipmentType $equipmentType)
    {
        $equipmentType->delete();

        return response()->json();
    }
}
