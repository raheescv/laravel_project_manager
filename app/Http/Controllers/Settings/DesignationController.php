<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Designation;
use Illuminate\Http\Request;

class DesignationController extends Controller
{
    public function index()
    {
        return view('settings.designation.index');
    }

    public function get(Request $request)
    {
        $list = (new Designation())->getDropDownList($request->all());

        return response()->json($list);
    }
}
