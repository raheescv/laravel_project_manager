<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\CustomerType;
use Illuminate\Http\Request;

class CustomerTypeController extends Controller
{
    public function index()
    {
        return view('settings.customer-type.index');
    }

    public function get(Request $request)
    {
        $list = (new CustomerType())->getDropDownList($request->all());

        return response()->json($list);
    }
}
