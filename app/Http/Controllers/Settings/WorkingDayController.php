<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;

class WorkingDayController extends Controller
{
    public function index()
    {
        return view('settings.working-day');
    }
}
