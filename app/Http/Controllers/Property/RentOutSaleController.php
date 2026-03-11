<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;

class RentOutSaleController extends Controller
{
    public function index()
    {
        return view('property.sale.index');
    }

    public function create($id = null)
    {
        return view('property.sale.create', ['id' => $id]);
    }

    public function view($id)
    {
        return view('property.sale.view', ['id' => $id]);
    }

    public function booking()
    {
        return view('property.sale.booking');
    }

    public function bookingCreate($id = null)
    {
        return view('property.sale.booking-create', ['id' => $id]);
    }

    public function bookingView($id)
    {
        return view('property.sale.booking-view', ['id' => $id]);
    }
}
