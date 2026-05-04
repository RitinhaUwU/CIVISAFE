<?php

namespace App\Http\Controllers\Equipment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Equipment\EquipmentEventRequest;
use App\Http\Resources\Equipment\EquipmentEventResource;
use App\Models\EquipmentEvent;

class EquipmentEventController extends Controller
{
    public function index()
    {
        return EquipmentEventResource::collection(EquipmentEvent::all());
    }

    public function store(EquipmentEventRequest $request)
    {
        return new EquipmentEventResource(EquipmentEvent::create($request->validated()));
    }

    public function show(EquipmentEvent $equipmentEvent)
    {
        return new EquipmentEventResource($equipmentEvent);
    }

    public function update(EquipmentEventRequest $request, EquipmentEvent $equipmentEvent)
    {
        $equipmentEvent->update($request->validated());

        return new EquipmentEventResource($equipmentEvent);
    }

    public function destroy(EquipmentEvent $equipmentEvent)
    {
        $equipmentEvent->delete();

        return response()->json();
    }
}
