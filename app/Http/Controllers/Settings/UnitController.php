<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function index()
    {
        return view('settings.unit.index');
    }

    public function get(Request $request)
    {
        $list = (new Unit)->getDropDownList($request->all());

        return response()->json($list);
    }
}
