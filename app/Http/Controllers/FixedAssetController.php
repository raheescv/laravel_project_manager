<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class FixedAssetController extends Controller
{
    public function index()
    {
        return view('asset.index');
    }

    public function page($id = null)
    {
        $type = 'asset';

        return view('product.page', compact('type', 'id'));
    }

    public function import()
    {
        return view('asset.import');
    }

    public function get(Request $request)
    {
        $list = (new Product())->getDropDownList($request->all());

        return response()->json($list);
    }
}
