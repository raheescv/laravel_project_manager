<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use Illuminate\Http\Request;

class ComplaintController extends Controller
{
    public function index()
    {
        return view('settings.complaint.index');
    }

    public function get(Request $request)
    {
        $list = (new Complaint())->getDropDownList($request->all());

        return response()->json($list);
    }
}
