<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class LogController extends Controller
{
    public function inventory(): View
    {
        return view('log.inventory');
    }

    public function jobs(): View
    {
        return view('log.jobs');
    }

    public function failedJobs(): View
    {
        return view('log.failed-jobs');
    }
}
