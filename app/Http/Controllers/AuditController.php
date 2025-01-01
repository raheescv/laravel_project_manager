<?php

namespace App\Http\Controllers;

class AuditController extends Controller
{
    public function index($model, $id)
    {
        return view('audit.index', compact('model', 'id'));
    }
}
