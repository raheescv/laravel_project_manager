<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        return view('settings.category.index');
    }

    public function get(Request $request)
    {
        $list = (new Category())->getDropDownList($request->all());

        return response()->json($list);
    }
}
