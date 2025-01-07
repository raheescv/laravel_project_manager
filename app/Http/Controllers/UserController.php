<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('user.index');
    }

    public function employee(): View
    {
        return view('user.employee');
    }

    public function view($id): View
    {
        return view('user.view', compact('id'));
    }

    public function get(Request $request)
    {
        $list = (new User)->getDropDownList($request->all());

        return response()->json($list);
    }
}
