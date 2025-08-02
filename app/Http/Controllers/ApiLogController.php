<?php

namespace App\Http\Controllers;

class ApiLogController extends Controller
{
    public function index()
    {
        return view('apilog.index');
    }

    public function moqSettings()
    {
        return view('apilog.moq_settings');
    }
}
