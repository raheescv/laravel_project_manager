<?php

namespace App\Http\Controllers;

class HealthController extends Controller
{
    public function index()
    {
        return view('system.health');
    }
}
