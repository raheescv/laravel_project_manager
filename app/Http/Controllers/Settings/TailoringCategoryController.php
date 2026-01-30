<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\TailoringCategory;
use Illuminate\Http\Request;

class TailoringCategoryController extends Controller
{
    public function index()
    {
        return view('settings.tailoring-category.index');
    }

    public function get(Request $request)
    {
        $list = (new TailoringCategory())->getDropDownList($request->all());

        return response()->json($list);
    }
}
