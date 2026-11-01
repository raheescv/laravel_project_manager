<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;

class ServiceRequestController extends Controller
{
    public function index()
    {
        return view('property.service-request.index');
    }

    public function create()
    {
        return view('property.service-request.create', ['id' => null]);
    }

    public function edit($id)
    {
        return view('property.service-request.create', ['id' => $id]);
    }

    public function report()
    {
        return view('property.service-request.report');
    }
}
