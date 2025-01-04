<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index()
    {
        return view('settings.branch.index');
    }

    public function get(Request $request)
    {
        $list = (new Branch)->getDropDownList($request->all());

        return response()->json($list);
    }
}
