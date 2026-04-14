<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\Models\PropertyType;
use Illuminate\Http\Request;

class PropertyTypeController extends Controller
{
    public function index()
    {
        return view('property.type.index');
    }

    public function get(Request $request)
    {
        $list = (new PropertyType())->getDropDownList($request->all());

        return response()->json($list);
    }
}
