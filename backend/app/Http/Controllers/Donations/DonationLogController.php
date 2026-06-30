<?php

namespace App\Http\Controllers\Donations;

use App\Events\StockUpdated;
use App\Http\Controllers\Controller;
use App\Http\Helpers\ActivityHelper;
use App\Http\Requests\Donations\DonationLogRequest;
use App\Http\Resources\Donations\DonationLogResource;
use App\Models\Donations\DonationContent;
use App\Models\Donations\DonationLog;
use App\Models\Donations\DonationStock;
use DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class DonationLogController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:DONATION_LOG_LIST')->only(['index', 'show']);
        $this->middleware('permission:DONATION_LOG_CREATE')->only(['store']);
        $this->middleware('permission:DONATION_LOG_UPDATE')->only(['update']);
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
            ->cursorPaginate($request->input('per_page', 15))
            ->appends($request->query());

        return DonationLogResource::collection($records);
    }

    public function store(DonationLogRequest $request)
    {
        try {
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
                    ->map(fn($group, $categoryId) => [
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
                        ->lockForUpdate()
                        ->incrementOrCreate([
                            'donation_goods_type_id' => $good['category_id'],
                        ], 'stock', $good['quantity']);

                    broadcast(new StockUpdated(DonationStock::where(['donation_goods_type_id' => $good['category_id']])->first()));
                }

                return $donation;
            });

            DB::commit();
            return new DonationLogResource($donation);
        } catch (\Throwable $e) {
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
        try {
            DB::transaction(function () use ($request, $donationLog) {
                $fields = collect($request->validated());

                $donationLog->update($fields->except('goods')->toArray());

                /**
                 * Ver o número anterior de goods
                 * Subtrair ou somar consoante a diferença
                 *
                 * Remover ou adicionar itens de novas categorias
                 */

                collect($fields['goods'])->each(function ($item) use ($donationLog) {

                    $good = DonationContent::where(['donation_log_id' => $donationLog->id])
                        ->where(['donation_goods_types_id' => $item['category_id']])
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
                            if ($diff > 0) {
                                DonationStock::where(['donation_goods_type_id' => $item['category_id']])
                                    ->lockForUpdate()
                                    ->decrement('stock', abs($diff));
                            } else {
                                DonationStock::where(['donation_goods_type_id' => $item['category_id']])
                                    ->lockForUpdate()
                                    ->increment('stock', abs($diff));
                            }
                            broadcast(new StockUpdated(DonationStock::where(['donation_goods_type_id' => $item['category_id']])->first()));
                        }
                    } else {
                        //Se o item não existe na db, adicionamos as unidades
                        DonationContent::create([
                            'donation_log_id' => $donationLog->id,
                            'donation_goods_types_id' => $item['category_id'],
                            'quantity' => $item['quantity'],
                        ]);

                        DonationStock::where(['donation_goods_type_id' => $item['category_id']])
                            ->lockForUpdate()
                            ->incrementOrCreate([
                                'donation_goods_type_id' => $item['category_id'],
                            ], 'stock', $item['quantity']);

                        broadcast(new StockUpdated(DonationStock::where(['donation_goods_type_id' => $item['category_id']])->first()));
                    }
                });

                //Se o item existe apenas na db mas não está no request é porque foi removido. Removemos todas as unidades
                DonationContent::where(['donation_log_id' => $donationLog->id])
                    ->get()
                    ->filter(function ($good) use ($fields) {
                        return !collect($fields['goods'])->contains('category_id', $good->donation_goods_types_id);
                    })
                    ->each(function ($good) {

                        DonationStock::where(['donation_goods_type_id' => $good->donation_goods_types_id])
                            ->lockForUpdate()
                            ->decrement('stock', $good->quantity);

                        $good->delete();
                        broadcast(new StockUpdated(DonationStock::where(['donation_goods_type_id' => $good->donation_goods_types_id])->first()));
                    });
            });

            DB::commit();
            return new DonationLogResource($donationLog->fresh());
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function audit(Request $request, DonationLog $donationLog)
    {
        $logs = QueryBuilder::for(Activity::class)
            ->where(function (Builder $query) use ($donationLog) {
                $query->where('subject_type', DonationLog::class)
                    ->where('subject_id', $donationLog->id);
            })
            ->orWhere(function (Builder $query) use ($donationLog) {
                $query->where('subject_type', DonationContent::class)
                    ->whereIn('subject_id', $donationLog->donationContent->pluck('id'));
            })
            ->with('causer')
            ->orderBy('id', 'desc')
            ->cursorPaginate($request->input('per_page', 20))
            ->through(function ($activity) {
                return [
                    'id' => $activity->id,
                    'type' => $activity->subject_type === DonationLog::class ? 'Doação' : 'Linha de Doação',
                    'action' => $activity->description,
                    'user' => $activity->causer?->name,
                    'date' => $activity->created_at->toISOString(),
                    'changes' => ActivityHelper::transformActivityValues($activity->attribute_changes['attributes'] ?? [], 'd/m/Y'),
                    'old_values' => ActivityHelper::transformActivityValues($activity->attribute_changes['old'] ?? [], 'd/m/Y'),
                ];
            });

        return response()->json($logs);
    }
}
