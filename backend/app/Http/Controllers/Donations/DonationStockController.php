<?php

namespace App\Http\Controllers\Donations;

use App\Events\Donations\StockLockUpdate;
use App\Events\Donations\StockUpdated;
use App\Http\Controllers\Controller;
use App\Http\Helpers\ActivityHelper;
use App\Http\Requests\DonationAuditRequest;
use App\Http\Resources\Donations\DonationAuditResource;
use App\Http\Resources\Donations\DonationStockResource;
use App\Models\AppSetting;
use App\Models\Donations\DistributionContent;
use App\Models\Donations\DonationAudit;
use App\Models\Donations\DonationContent;
use App\Models\Donations\DonationDistribution;
use App\Models\Donations\DonationLog;
use App\Models\Donations\DonationStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;
use Spatie\QueryBuilder\QueryBuilder;

class DonationStockController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:DONATION_LOG_LIST')->only(['index', 'getUnlock']);
        $this->middleware('permission:DONATION_LOG_CREATE')->only(['store']);
        $this->middleware('permission:SETTING_DONATION_DISTRIBUTION_STOCK_UNLOCK')->only('storeUnlock');
    }

    public function index(Request $request)
    {
        $logs = QueryBuilder::for(Activity::class)
            ->where(function ($query) {
                $query->where('subject_type', DonationLog::class)
                    ->orWhere('subject_type', DonationContent::class)
                    ->orWhere('subject_type', DonationDistribution::class)
                    ->orWhere('subject_type', DistributionContent::class)
                    ->orWhere('subject_type', DonationAudit::class);
            })
            ->with('causer')
            ->orderBy('id', 'desc')
            ->cursorPaginate($request->input('per_page', 20))
            ->through(function ($activity) {
                return [
                    'id' => $activity->id,
                    'type' => $this->subjectTypeResolver($activity->subject_type),
                    'action' => $activity->description,
                    'user' => $activity->causer?->name,
                    'date' => $activity->created_at->toISOString(),
                    'changes' => ActivityHelper::transformActivityValues($activity->attribute_changes['attributes'] ?? [], 'd/m/Y'),
                    'old_values' => ActivityHelper::transformActivityValues($activity->attribute_changes['old'] ?? [], 'd/m/Y'),
                ];
            });

        return response()->json($logs);
    }

    private function subjectTypeResolver($model)
    {
        return match ($model) {
            DonationLog::class => "Doação",
            DonationContent::class => "Linha de Doação",
            DonationDistribution::class => "Entrega",
            DistributionContent::class => "Linha de Entrega",
            DonationAudit::class => "Atualização de Stock",
            default => $model,
        };
    }

    public function store(DonationAuditRequest $request)
    {
        try {
            $audit = DB::transaction(function () use ($request) {
                $audit = DonationAudit::create([
                    'adjustment_type' => $request->validated('adjustment_type'),
                    'donation_goods_type_id' => $request->validated('category_id'),
                    'quantity' => $request->validated('quantity'),
                    'reason' => $request->validated('reason'),
                    'obs' => $request->validated('obs') ?? null,
                    'user_id' => $request->user()->id,
                ]);

                if ($request->validated('adjustment_type') == 'add') {
                    DonationStock::where(['donation_goods_type_id' => $request->validated('category_id')])
                        ->lockForUpdate()
                        ->incrementOrCreate([
                            'donation_goods_type_id' => $request->validated('category_id'),
                        ], 'stock', $request->validated('quantity'), $request->validated('quantity'));
                } else {
                    DonationStock::upsert(
                        [
                            'donation_goods_type_id' => $request->validated('category_id'),
                            'stock' => (-$request->validated('quantity')),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ],
                        'donation_goods_type_id',
                        [
                            'stock' => DB::raw('"donation_stocks".stock - ' . ($request->validated('quantity'))),
                            'updated_at' => now()
                        ]
                    );
                }

                broadcast(new StockUpdated(DonationStock::where(['donation_goods_type_id' => $request->validated('category_id')])->first()));
                return $audit;
            });

            DB::commit();
            return new DonationAuditResource($audit->fresh());
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function stock(Request $request)
    {
        return DonationStockResource::collection(DonationStock::all());
    }

    public function getUnlock(Request $request)
    {
        $setting = AppSetting::firstOrCreate(['name' => 'donation_stock_unlocked'], ['state' => false]);

        return response()->json($setting->toArray());
    }

    public function storeUnlock(Request $request)
    {
        $validated = $request->validate([
            'state' => ['required', 'boolean'],
        ]);

        AppSetting::firstOrNew(['name' => 'donation_stock_unlocked'])
            ->update(['state' => $validated['state']]);

        $setting = AppSetting::where(['name' => 'donation_stock_unlocked'])->first();

        broadcast(new StockLockUpdate($setting->state));

        return response()->json($setting->toArray());
    }
}
