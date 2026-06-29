<?php

namespace App\Http\Controllers\Donations;

use App\Http\Controllers\Controller;
use App\Http\Helpers\ActivityHelper;
use App\Models\Donations\DistributionContent;
use App\Models\Donations\DonationContent;
use App\Models\Donations\DonationDistribution;
use App\Models\Donations\DonationLog;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;
use Spatie\QueryBuilder\QueryBuilder;

class DonationAuditController extends Controller
{
    public function index(Request $request)
    {
        $logs = QueryBuilder::for(Activity::class)
            ->where(function ($query) {
                $query->where('subject_type', DonationLog::class)
                    ->orWhere('subject_type', DonationContent::class)
                    ->orWhere('subject_type', DonationDistribution::class)
                    ->orWhere('subject_type', DistributionContent::class);
            })
            ->with('causer')
            ->orderBy('id', 'desc')
            ->cursorPaginate($request->input('per_page', 20), cursor: $request->input('cursor'))
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
            default => get_class($model),
        };
    }

    public function store(Request $request)
    {

    }
}
