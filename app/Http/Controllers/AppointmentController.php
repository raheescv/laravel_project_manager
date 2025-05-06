<?php

namespace App\Http\Controllers;

class AppointmentController extends Controller
{
    public function index()
    {
        return view('appointment.index');
    }
}
