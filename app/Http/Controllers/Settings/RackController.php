<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Rack;
use Illuminate\Http\Request;

class RackController extends Controller
{
    public function index()
    {
        return view('settings.rack.index');
    }

    public function get(Request $request)
    {
        $list = (new Rack())->getDropDownList($request->all());

        return response()->json($list);
    }
}
