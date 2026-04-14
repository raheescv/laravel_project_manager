<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\Models\Utility;
use Illuminate\Http\Request;

class UtilityController extends Controller
{
    public function index()
    {
        return view('property.utility.index');
    }

    public function get(Request $request)
    {
        $list = (new Utility())->getDropDownList($request->all());

        return response()->json($list);
    }
}
