<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class TenantController extends Controller
{
    public function index(): View
    {
        return view('tenant.index');
    }
}
