<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index()
    {
        return view('settings.department.index');
    }

    public function get(Request $request)
    {
        $list = (new Department)->getDropDownList($request->all());

        return response()->json($list);
    }
}
