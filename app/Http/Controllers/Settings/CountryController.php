<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Country;
use Illuminate\Http\Request;

class CountryController extends Controller
{
    public function index()
    {
        return view('settings.country.index');
    }

    public function get(Request $request)
    {
        $list = (new Country())->getDropDownList($request->all());

        return response()->json($list);
    }
}
