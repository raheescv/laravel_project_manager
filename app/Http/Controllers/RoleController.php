<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(): View
    {
        return view('settings.role.index');
    }
}
