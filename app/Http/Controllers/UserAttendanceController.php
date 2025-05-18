<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class UserAttendanceController extends Controller
{
    public function index(): View
    {
        return view('user.attendance');
    }
}
