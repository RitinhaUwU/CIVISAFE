<?php

namespace App\Http\Controllers\Equipment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Equipment\EquipmentRequest;
use App\Http\Resources\Equipment\EquipmentResource;
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
