<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(): View
    {
        return view('settings.role.index');
    }

    public function permissions($id)
    {
        return view('settings.role.permissions', compact('id'));
    }
}
