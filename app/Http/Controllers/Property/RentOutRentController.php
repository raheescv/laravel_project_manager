<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;

class RentOutRentController extends Controller
{
    public function index()
    {
        return view('property.rent.index');
    }

    public function create($id = null)
    {
        return view('property.rent.create', ['id' => $id]);
    }

    public function view($id)
    {
        return view('property.rent.view', ['id' => $id]);
    }

    public function booking()
    {
        return view('property.rent.booking');
    }

    public function bookingCreate($id = null)
    {
        return view('property.rent.booking-create', ['id' => $id]);
    }

    public function bookingView($id)
    {
        return view('property.rent.booking-view', ['id' => $id]);
    }
}
