<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;

class HolidayController extends Controller
{
    public function index()
    {
        return view('settings.holiday.index');
    }
}
