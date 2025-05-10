<?php

namespace App\Http\Controllers;

use App\Models\ComboOffer;
use Illuminate\Http\Request;

class ComboOfferController extends Controller
{
    public function index()
    {
        return view('combo_offer.index');
    }

    public function get(Request $request)
    {
        $list = (new ComboOffer())->getDropDownList($request->all());

        return response()->json($list);
    }
}
