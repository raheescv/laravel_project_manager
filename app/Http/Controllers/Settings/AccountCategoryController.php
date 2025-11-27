<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\AccountCategory;
use Illuminate\Http\Request;

class AccountCategoryController extends Controller
{
    public function index()
    {
        return view('settings.account-category.index');
    }

    public function get(Request $request)
    {
        $list = (new AccountCategory())->getDropDownList($request->all());

        return response()->json($list);
    }
}
