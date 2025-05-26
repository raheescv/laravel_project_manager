<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    public function index()
    {
        return view('purchase.index');
    }

    public function page($id = null)
    {
        return view('purchase.page', compact('id'));
    }

    public function payments()
    {
        return view('purchase.payments');
    }

    public function get(Request $request)
    {
        $list = (new Purchase())->getDropDownList($request->all());

        return response()->json($list);
    }
}
