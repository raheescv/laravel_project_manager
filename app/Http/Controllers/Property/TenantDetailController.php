<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\Models\TenantDetail;
use Illuminate\Http\Request;

class TenantDetailController extends Controller
{
    public function index()
    {
        return view('property.tenant.index');
    }

    public function get(Request $request)
    {
        $list = (new TenantDetail())->getDropDownList($request->all());

        return response()->json($list);
    }
}
