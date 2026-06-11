<?php

namespace App\Http\Controllers;

use App\Http\Requests\Donations\DonationLogRequest;
use App\Http\Resources\Donations\DonationLogResource;
use App\Models\Donations\DonationLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class DonationLogController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:DONATION_LOG_LIST')->only(['index', 'show']);
        $this->middleware('permission:DONATION_LOG_CREATE')->only(['store']);
        $this->middleware('permission:DONATION_LOG_UPDATE')->only(['update']);
        $this->middleware('permission:DONATION_LOG_DELETE')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $request->validate([
            'per_page' => 'sometimes|integer',
        ]);

        $records = QueryBuilder::for(DonationLog::class)
            ->allowedFilters(
                AllowedFilter::callback('search', function (Builder $query, $value) {
                    $query->where('name', 'ILIKE', "%{$value}%");
                })
            )
        ->orderBy('id', 'desc')
        ->paginate($request->input('per_page', 15))
        ->appends($request->query());

        return DonationLogResource::collection($records);
    }

    public function store(DonationLogRequest $request)
    {
        return new DonationLogResource(DonationLog::create($request->validated()));
    }

    public function show(DonationLog $donationLog)
    {
        return new DonationLogResource($donationLog);
    }

    public function update(DonationLogRequest $request, DonationLog $donationLog)
    {
        $donationLog->update($request->validated());

        return new DonationLogResource($donationLog);
    }

    public function destroy(DonationLog $donationLog)
    {
        $donationLog->delete();

        return response()->json();
    }
}
