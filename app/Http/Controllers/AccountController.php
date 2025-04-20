<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function index()
    {
        return view('accounts.index');
    }

    public function customer($id = null)
    {
        if ($id) {
            return view('accounts.customer_details', compact('id'));
        } else {
            return view('accounts.customer');
        }
    }

    public function vendor()
    {
        return view('accounts.vendor');
    }

    public function get(Request $request)
    {
        $list = (new Account())->getDropDownList($request->all());

        return response()->json($list);
    }

    public function view($id)
    {
        $account = Account::findOrFail($id);

        return view('accounts.view', compact('id', 'account'));
    }
}
