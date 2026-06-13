<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Checklist;
use Illuminate\Http\Request;

class ChecklistItemController extends Controller
{
    public function index()
    {
        return view('settings.checklist-item.index');
    }

    public function get(Request $request)
    {
        $list = (new Checklist())->getDropDownList($request->all());

        return response()->json($list);
    }
}
