<?php

namespace App\Http\Controllers;

use App\Models\ServicePackage;
use Illuminate\Http\Request;

class ServicePackageController extends Controller
{
    public function index()
    {
        return view('service.package');
    }

    public function get(Request $request)
    {
        $list = (new ServicePackage())->getDropDownList($request->all());

        return response()->json($list);
    }
}
