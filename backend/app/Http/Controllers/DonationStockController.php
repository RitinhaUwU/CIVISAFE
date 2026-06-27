<?php

namespace App\Http\Controllers;

use App\Http\Resources\Donations\DonationStockResource;
use App\Models\Donations\DonationStock;
use Illuminate\Http\Request;

class DonationStockController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:DONATION_LOG_LIST')->only(['stock']);
    }

    public function stock(Request $request)
    {
        return DonationStockResource::collection(DonationStock::all());
    }
}
