<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;

class EmailTemplateController extends Controller
{
    public function index()
    {
        return view('settings.email-template.index');
    }
}
