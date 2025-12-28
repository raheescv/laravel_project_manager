<?php

namespace App\Http\Controllers;

use App\Models\PackageCategory;
use Illuminate\Http\Request;

class PackageCategoryController extends Controller
{
    public function index()
    {
        return view('package-category.index');
    }

    public function get(Request $request)
    {
        $list = (new PackageCategory())->getDropDownList($request->all());

        return response()->json($list);
    }
}

