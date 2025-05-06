<?php

namespace App\Http\Controllers;

class AppointmentController extends Controller
{
    public function calendar()
    {
        return view('appointment.employee-calendar');
    }

    public function index()
    {
        return view('appointment.index');
    }
}
