<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    public function index()
    {
        return view('sale.index');
    }

    public function page($id = null)
    {
        return view('sale.page', compact('id'));
    }

    public function view($id)
    {
        return view('sale.view', compact('id'));
    }

    public function receipts()
    {
        return view('sale.receipts');
    }

    public function get(Request $request)
    {
        $list = (new Sale())->getDropDownList($request->all());

        return response()->json($list);
    }
}
