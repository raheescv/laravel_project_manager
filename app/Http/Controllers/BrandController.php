<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    public function get(Request $request)
    {
        $list = (new Product())->getBrandDropDownList($request->all());

        return response()->json($list);
    }
}
