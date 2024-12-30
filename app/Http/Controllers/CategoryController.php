<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        return view('category.index');
    }

    public function get(Request $request)
    {
        $list = (new Category)->getDropDownList($request->all());

        return response()->json($list);
    }
}
