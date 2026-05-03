<?php

namespace App\Http\Controllers;

use App\Http\Requests\FacilitiesRequest;
use App\Http\Resources\FacilityResource;
use App\Models\Facility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class FacilitiesController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:FACILITIES_LIST')->only(['index', 'show']);
        $this->middleware('permission:FACILITIES_CREATE')->only(['store']);
        $this->middleware('permission:FACILITIES_UPDATE')->only(['update']);
        $this->middleware('permission:FACILITIES_DELETE')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $facility = QueryBuilder::for(Facility::class)
            ->allowedFilters(
                AllowedFilter::callback('search', function (Builder $query, $value) {
                    $query->where(function (Builder $q) use ($value) {
                        $q->where('name', 'ILIKE', "%{$value}%");
                    });
                }),
            )
            ->paginate($request->input('per_page', 15))
            ->appends($request->query());

        return FacilityResource::collection($facility);    }

    public function store(FacilitiesRequest $request)
    {
        return new FacilityResource(Facility::create($request->validated()));
    }

    public function show(Facility $facility)
    {
        return new FacilityResource($facility);
    }

    public function update(FacilitiesRequest $request, Facility $facility)
    {
        $facility->update($request->validated());

        return new FacilityResource($facility);
    }

    public function destroy(Facility $facility)
    {
        $facility->delete();

        return response()->json();
    }
}
