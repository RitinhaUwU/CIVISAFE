<?php

namespace App\Http\Controllers;

use App\Http\Requests\VolunteerRequest;
use App\Http\Resources\VolunteerResource;
use App\Models\Volunteer;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Database\Eloquent\Builder;

class VolunteerController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:VOLUNTEERS_LIST')->only(['index', 'show']);
        $this->middleware('permission:VOLUNTEERS_CREATE')->only(['store']);
        $this->middleware('permission:VOLUNTEERS_UPDATE')->only(['update']);
        $this->middleware('permission:VOLUNTEERS_DELETE')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $request->validate([
            'per_page' => 'sometimes|integer',
        ]);

        $volunteer = QueryBuilder::for(Volunteer::class)
            ->allowedFilters(
                AllowedFilter::callback('search', function (Builder $query, $value) {
                    $query->where(function (Builder $q) use ($value) {
                        $q->where('name', 'ILIKE', "%{$value}%")
                            ->orWhere('team_identification', 'ILIKE', "%{$value}%");
                    });
                }),
                AllowedFilter::callback('classification', function (Builder $query, $value) {
                    if ($value === 'all' || $value === null) {
                        return;
                    }
                    $query->where('classification', $value);
                }),
                AllowedFilter::callback('has_accommodation', function (Builder $query, $value) {
                    if ($value === 'all' || $value === null) {
                        return;
                    }
                    $query->where('has_accommodation', $value);
                }),
                AllowedFilter::callback('has_meal', function (Builder $query, $value) {
                    if ($value === 'all' || $value === null) {
                        return;
                    }
                    $query->where('has_meal', $value);
                }),
            )
            ->orderBy('id', 'desc')
            ->paginate($request->input('per_page', 15))
            ->appends($request->query());

        return VolunteerResource::collection($volunteer);
    }

    public function store(VolunteerRequest $request)
    {
        return new VolunteerResource(Volunteer::create($request->validated()));
    }

    public function show(Volunteer $volunteer)
    {
        return new VolunteerResource($volunteer->load([
            'incident',
        ]));
    }

    public function update(VolunteerRequest $request, Volunteer $volunteer)
    {
        $volunteer->update($request->validated());

        return new VolunteerResource(
            $volunteer->fresh()->load('incident')
        );
    }

    public function destroy(Volunteer $volunteer)
    {
        $volunteer->delete();

        return response()->json();
    }
}
