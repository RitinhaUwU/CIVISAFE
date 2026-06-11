<?php

namespace App\Http\Controllers;

use App\Http\Requests\Donations\DonationGoodsTypeRequest;
use App\Http\Resources\Donations\DonationGoodsTypeResource;
use App\Models\Donations\DonationGoodsType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class DonationGoodsTypeController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:DONATION_GOODS_TYPES_LIST')->only(['index', 'show']);
        $this->middleware('permission:DONATION_GOODS_TYPES_CREATE')->only(['store']);
        $this->middleware('permission:DONATION_GOODS_TYPES_UPDATE')->only(['update']);
        $this->middleware('permission:DONATION_GOODS_TYPES_DELETE')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $request->validate([
            'per_page' => 'sometimes|integer',
        ]);

        $records = QueryBuilder::for(DonationGoodsType::class)
            ->allowedFilters(
                AllowedFilter::callback('search', function (Builder $query, $value) {
                    $query->where('name', 'ILIKE', "%{$value}%");
                })
            )
            ->orderBy('id', 'asc')
            ->paginate($request->input('per_page', 10))
            ->appends($request->query());

        return DonationGoodsTypeResource::collection($records);
    }

    public function store(DonationGoodsTypeRequest $request)
    {
        $data = $request->validated();

        $data['unit'] = $data['is_type_countable'] ? $data['unit'] : null;

        return new DonationGoodsTypeResource(DonationGoodsType::create($data));
    }

    public function show(DonationGoodsType $donationGoodsType)
    {
        return new DonationGoodsTypeResource($donationGoodsType);
    }

    public function update(DonationGoodsTypeRequest $request, DonationGoodsType $donationGoodsType)
    {
        $data = $request->validated();

        $data['unit'] = $data['is_type_countable'] ? $data['unit'] : null;

        $donationGoodsType->update($data);

        return new DonationGoodsTypeResource($donationGoodsType);
    }

    public function destroy(DonationGoodsType $donationGoodsType)
    {
        $donationGoodsType->delete();

        return response()->json();
    }
}
