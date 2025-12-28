<?php

namespace App\Http\Controllers;

use App\Models\Package;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    public function index()
    {
        return view('package.index');
    }

    public function get(Request $request)
    {
        $list = (new Package())->getDropDownList($request->all());

        return response()->json($list);
    }
}

