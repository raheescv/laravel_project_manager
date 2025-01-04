<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        return view('product.index');
    }

    public function page($id = null)
    {
        return view('product.page', compact('id'));
    }

    public function get(Request $request)
    {
        $list = (new Product)->getDropDownList($request->all());

        return response()->json($list);
    }
}
