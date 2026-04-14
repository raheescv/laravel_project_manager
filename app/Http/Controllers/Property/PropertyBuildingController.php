<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\Models\PropertyBuilding;
use Illuminate\Http\Request;

class PropertyBuildingController extends Controller
{
    public function index()
    {
        return view('property.building.index');
    }

    public function get(Request $request)
    {
        $list = (new PropertyBuilding())->getDropDownList($request->all());

        return response()->json($list);
    }
}
