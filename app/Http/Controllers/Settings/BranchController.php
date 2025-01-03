<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;

class BranchController extends Controller
{
    public function index()
    {
        return view('settings.branch.index');
    }
}
