<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class DonationStatsController extends Controller
{

    public function stats()
    {
        $totalStock = DB::table('donation_stocks')
            ->sum('stock');

        $stockAlerts = DB::table('donation_stocks')
            ->join('donation_goods_types', 'donation_goods_types.id', '=', 'donation_stocks.donation_goods_type_id')
            ->whereColumn('donation_stocks.stock', '<=', 'donation_goods_types.danger_level')
            ->select('name', 'stock', 'unit', 'danger_level')
            ->get();

        $topStockRaw = DB::table('donation_stocks')
            ->join('donation_goods_types', 'donation_goods_types.id', '=', 'donation_stocks.donation_goods_type_id')
            ->select('name', 'stock', 'unit', 'danger_level')
            ->orderBy('stock', 'desc')
            ->get();

        $topStock = $topStockRaw->take(5)->map(function ($item) {
            return [
                'name' => $item->name,
                'stock' => $item->stock,
                'unit' => $item->unit,
                'danger_level' => $item->danger_level
            ];
        });

        $rest = $topStockRaw->skip(5)->sum('stock');
        if($rest > 0)
        {
            $topStock->push([
                'name' => 'Restantes',
                'stock' => $rest,
                'unit' => null,
                'danger_level' => null,
            ]);
        }


        $lowestStock = DB::table('donation_stocks')
            ->join('donation_goods_types', 'donation_goods_types.id', '=', 'donation_stocks.donation_goods_type_id')
            ->select('name', 'stock', 'unit', 'danger_level')
            ->orderBy('stock', 'asc')
            ->limit(5)
            ->get();

        $lowestStock = $lowestStock->sortBy('name')->values();


        return response()->json([
            'totalStock' => $totalStock,
            'stockAlerts' => $stockAlerts,
            'topStock' => $topStock,
            'lowestStock' => $lowestStock,
        ]);
    }
}
