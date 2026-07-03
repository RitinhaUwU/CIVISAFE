<?php

namespace App\Http\Controllers\Donations;

use App\Events\DistributionCreated;
use App\Events\DistributionUpdated;
use App\Events\StockUpdated;
use App\Http\Controllers\Controller;
use App\Http\Helpers\ActivityHelper;
use App\Http\Requests\Donations\DonationDistributionRequest;
use App\Http\Resources\Donations\DonationDistributionResource;
use App\Models\AppSetting;
use App\Models\Donations\DistributionContent;
use App\Models\Donations\DonationDistribution;
use App\Models\Donations\DonationStock;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;
use Spatie\QueryBuilder\QueryBuilder;

class DonationDistributionController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:DONATION_LOG_LIST')->only(['index', 'show', 'getRules']);
        $this->middleware('permission:DONATION_LOG_CREATE')->only(['store']);
        $this->middleware('permission:DONATION_LOG_UPDATE')->only(['update']);
        $this->middleware('permission:SETTING_DONATION_DISTRIBUTION_RULES_UPDATE')->only(['storeRules']);
    }

    public function index(Request $request)
    {
        $request->validate([
            'per_page' => 'sometimes|integer'
        ]);

        $distributions = QueryBuilder::for(DonationDistribution::class)
            ->orderBy('created_at', 'desc')
            ->cursorPaginate($request->input('per_page', 10))
            ->appends($request->query());

        return DonationDistributionResource::collection($distributions);
    }

    public function store(DonationDistributionRequest $request)
    {
        try {
            $goods = collect($request->validated('goods'))
                ->groupBy('category_id')
                ->map(fn($group, $categoryId) => [
                    'category_id' => $categoryId,
                    'quantity' => $group->sum('quantity')
                ])
                ->values()->all();

            $distribution = DB::transaction(function () use ($request, $goods) {
                $distribution = DonationDistribution::create([
                    'name' => $request->validated('name'),
                    'contact' => $request->validated('contact'),
                    'obs' => $request->validated('obs'),
                    'user_id' => Auth::user()->id,
                ]);

                foreach ($goods as $good) {
                    DistributionContent::create([
                        'donation_distribution_id' => $distribution->id,
                        'donation_goods_type_id' => $good['category_id'],
                        'quantity' => $good['quantity'],
                    ]);

                    if(DonationStock::where(['donation_goods_type_id' => $good['category_id']])->exists()) {
                        DonationStock::where(['donation_goods_type_id' => $good['category_id']])
                            ->lockForUpdate()
                            ->decrement('stock', $good['quantity']);
                    }
                    else
                    {
                        DonationStock::upsert(
                            [
                                'donation_goods_type_id' => $good['category_id'],
                                'stock' => (-$good['quantity']),
                                'created_at' => now(),
                                'updated_at' => now(),
                            ],
                            'donation_goods_type_id',
                            [
                                'stock' => DB::raw('"donation_stocks".stock + ' . (-$good['quantity'])),
                                'updated_at' => now()
                            ]
                        );
                    }
                }

                return $distribution;
            });

            DB::commit();

            foreach($goods as $good)
            {
                broadcast(new StockUpdated(DonationStock::where(['donation_goods_type_id' => $good['category_id']])->first()));
            }

            broadcast(new DistributionCreated($distribution));

            return new DonationDistributionResource($distribution);
        }
        catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function show(DonationDistribution $donationDistribution)
    {
        return new DonationDistributionResource($donationDistribution);
    }

    public function update(DonationDistributionRequest $request, DonationDistribution $donationDistribution)
    {
        /***
         * A lógica do update da distribuição é muito semelhante à do update dos donativos, contudo, aqui a
         * lógica de incrementar/decrementar tem de ser invertida, visto que a distribuição de bens é o
         * inverso da recolha
         */

        try {
            $goods = collect($request->validated('goods'))
                ->groupBy('category_id')
                ->map(fn($group, $categoryId) => [
                    'category_id' => $categoryId,
                    'quantity' => $group->sum('quantity')
                ]);

            DB::transaction(function () use ($request, $donationDistribution, $goods) {
                $fields = collect($request->validated());

                $donationDistribution->update($fields->except('goods')->toArray());

                $goods->each(function ($item) use ($donationDistribution) {

                    $good = DistributionContent::where(['donation_distribution_id' => $donationDistribution->id])
                        ->where(['donation_goods_type_id' => $item['category_id']])
                        ->first();

                    //Se o item existe tanto na db como no pedido, então vemos a diferença de unidades
                    if ($good) {
                        //   Valor atual DB     |  Val. Recebido
                        //         15           -       12 =  3
                        //         15           -       20 = -5
                        $diff = $good->quantity - $item['quantity'];
                        if ($diff != 0) {
                            $good->quantity = $item['quantity'];
                            $good->save();

                            //É necessário fazer desta forma porque as respetivas funções não permitem passar valores inversos
                            if($diff < 0)
                            {
                                DonationStock::where(['donation_goods_type_id' => $item['category_id']])
                                    ->lockForUpdate()
                                    ->decrement('stock', abs($diff));
                            }
                            else
                            {
                                DonationStock::where(['donation_goods_type_id' => $item['category_id']])
                                    ->lockForUpdate()
                                    ->increment('stock', abs($diff));
                            }
                        }
                    } else {
                        //Se o item não existe na db, adicionamos as unidades
                        DistributionContent::create([
                            'donation_distribution_id' => $donationDistribution->id,
                            'donation_goods_type_id' => $item['category_id'],
                            'quantity' => $item['quantity'],
                        ]);

                        DonationStock::where(['donation_goods_type_id' => $item['category_id']])
                            ->lockForUpdate()
                            ->decrement('stock', $item['quantity']);
                    }
                });

                //Se o item existe apenas na db mas não está no request é porque foi removido. Removemos todas as unidades
                DistributionContent::where(['donation_distribution_id' => $donationDistribution->id])
                    ->get()
                    ->filter(function ($good) use ($fields) {
                        return !collect($fields['goods'])->contains('category_id', $good->donation_goods_type_id);
                    })
                    ->each(function ($good) {

                        DonationStock::where(['donation_goods_type_id' => $good->donation_goods_type_id])
                            ->lockForUpdate()
                            ->increment('stock', $good->quantity);

                        $good->delete();
                    });
            });

            DB::commit();

            $goods->each(function ($item) use ($donationDistribution) {
                broadcast(new StockUpdated(DonationStock::where(['donation_goods_type_id' => $item['category_id']])->first()));
            });

            broadcast(new DistributionUpdated($donationDistribution));

            return new DonationDistributionResource($donationDistribution->fresh());
        }
        catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function audit(Request $request, DonationDistribution $donationDistribution)
    {
        $logs = QueryBuilder::for(Activity::class)
            ->where(function (Builder $query) use ($donationDistribution) {
                $query->where('subject_type', DonationDistribution::class)
                    ->where('subject_id', $donationDistribution->id);
            })
            ->orWhere(function (Builder $query) use ($donationDistribution) {
                $query->where('subject_type', DistributionContent::class)
                    ->whereIn('subject_id', $donationDistribution->distributionContent->pluck('id')->toArray());
            })
            ->with('causer')
            ->orderBy('id', 'desc')
            ->cursorPaginate($request->input('per_page', 15))
            ->through(function ($activity) {
                return [
                    'id' => $activity->id,
                    'type' => $activity->subject_type === DonationDistribution::class ? 'Entrega' : 'Linha de Entrega',
                    'action' => $activity->description,
                    'user' => $activity->causer?->name,
                    'date' => $activity->created_at->toISOString(),
                    'changes' => ActivityHelper::transformActivityValues($activity->attribute_changes['attributes'] ?? [], 'd/m/Y'),
                    'old_values' => ActivityHelper::transformActivityValues($activity->attribute_changes['old'] ?? [], 'd/m/Y'),
                ];
            });

        return response()->json($logs);
    }

    public function getRules(Request $request)
    {
        $setting = AppSetting::firstOrCreate(['name' => 'donation_distribution_rules'], ['state' => '']);

        return response()->json($setting->toArray());
    }

    public function storeRules(Request $request)
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:4000000000'],
        ]);

        AppSetting::firstOrNew(['name' => 'donation_distribution_rules'])
            ->update(['state' => $validated['body']]);

        return response()->json(AppSetting::where(['name' => 'donation_distribution_rules'])->first()->toArray());
    }
}
