<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index()
    {
        return view('service.index');
    }

    public function page($id = null)
    {
        $type = 'service';

        return view('product.page', compact('type', 'id'));
    }

    public function get(Request $request)
    {
        $list = (new Product)->getDropDownList($request->all());

        return response()->json($list);
    }
}
