<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('user.index');
    }

    public function get($id): View
    {
        return view('user.view', compact('id'));
    }
}
