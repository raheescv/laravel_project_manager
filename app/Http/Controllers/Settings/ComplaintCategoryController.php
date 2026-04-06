<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\ComplaintCategory;
use Illuminate\Http\Request;

class ComplaintCategoryController extends Controller
{
    public function index()
    {
        return view('settings.complaint-category.index');
    }

    public function get(Request $request)
    {
        $list = (new ComplaintCategory())->getDropDownList($request->all());

        return response()->json($list);
    }
}
