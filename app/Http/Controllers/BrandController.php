<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    public function index()
    {
        return view('settings.brand.index');
    }

    public function get(Request $request)
    {
        $list = (new Brand())->getDropDownList($request->all());

        return response()->json($list);
    }
}
