<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;

class MaintenanceController extends Controller
{
    public function index()
    {
        return view('property.maintenance.index');
    }

    public function create()
    {
        return view('property.maintenance.create', ['id' => null]);
    }

    public function edit($id)
    {
        return view('property.maintenance.create', ['id' => $id]);
    }

    public function assign($id)
    {
        return view('property.maintenance.assign', ['id' => $id]);
    }

    public function complaint($id)
    {
        return view('property.maintenance.complaint', ['id' => $id]);
    }
}
