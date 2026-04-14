<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;

class RentOutReportController extends Controller
{
    public function customerProperty()
    {
        return view('property.report.customer-property');
    }

    public function security()
    {
        return view('property.report.security');
    }

    public function serviceCharge()
    {
        return view('property.report.service-charge');
    }

    public function daybook()
    {
        return view('property.report.daybook');
    }
}
