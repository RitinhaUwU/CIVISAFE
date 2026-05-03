<?php

namespace App\Http\Controllers;

use App\Http\Requests\EquipmentRequest;
use App\Http\Resources\EquipmentResource;
use App\Models\Equipment;

class EquipmentController extends Controller
{
    public function index()
    {
        return EquipmentResource::collection(Equipment::all());
    }

    public function store(EquipmentRequest $request)
    {
        return new EquipmentResource(Equipment::create($request->validated()));
    }

    public function show(Equipment $equipment)
    {
        return new EquipmentResource($equipment);
    }

    public function update(EquipmentRequest $request, Equipment $equipment)
    {
        $equipment->update($request->validated());

        return new EquipmentResource($equipment);
    }

    public function destroy(Equipment $equipment)
    {
        $equipment->delete();

        return response()->json();
    }
}
