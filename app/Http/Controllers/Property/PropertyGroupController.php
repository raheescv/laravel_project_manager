<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\Models\PropertyGroup;
use Illuminate\Http\Request;

class PropertyGroupController extends Controller
{
    public function index()
    {
        return view('property.group.index');
    }

    public function get(Request $request)
    {
        $list = (new PropertyGroup())->getDropDownList($request->all());

        return response()->json($list);
    }
}
