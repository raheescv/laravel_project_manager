<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\Models\Property;
use Illuminate\Http\Request;

class PropertyController extends Controller
{
    public function index()
    {
        return view('property.property.index');
    }

    public function get(Request $request)
    {
        $list = (new Property())->getDropDownList($request->all());

        return response()->json($list);
    }
}
