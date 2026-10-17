<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;

class TenantDetailController extends Controller
{
    public function index()
    {
        return view('property.tenant.index');
    }
}
