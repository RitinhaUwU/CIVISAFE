<?php

namespace App\Http\Controllers;

use App\Http\Requests\Donations\DonationLogRequest;
use App\Http\Resources\Donations\DonationLogResource;
use App\Models\Donations\DonationContent;
use App\Models\Donations\DonationLog;
use App\Models\Donations\DonationStock;
use DB;
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
        try
        {
            $donation = DB::transaction(function () use ($request) {
                $donation = DonationLog::create([
                    'date' => $request->validated('date'),
                    'name' => $request->validated('name'),
                    'contact' => $request->validated('contact'),
                    'email' => $request->validated('email'),
                    'donor_type' => $request->validated('donor_type'),
                    'user_id' => $request->user()->id
                ]);

                $goods = collect($request->validated('goods'))
                    ->groupBy('category_id')
                    ->map(fn ($group, $categoryId) => [
                        'category_id' => $categoryId,
                        'quantity' => $group->sum('quantity')
                    ])
                    ->values()->all();

                foreach ($goods as $good) {
                    DonationContent::create([
                        'donation_log_id' => $donation->id,
                        'donation_goods_types_id' => $good['category_id'],
                        'quantity' => $good['quantity'],
                    ]);

                    DonationStock::where(['donation_goods_type_id' => $good['category_id']])
                        ->increment('stock', $good['quantity']);
                }

                return $donation;
            });

            DB::commit();
            return new DonationLogResource($donation);
        }
        catch (\Throwable $e)
        {
            DB::rollBack();

            return response()->json(['message' => $e->getMessage()], 500);
        }
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
