<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\Livewire\Property\PropertyLead\Calendar;
use Illuminate\Http\Request;

class PropertyLeadController extends Controller
{
    public function index()
    {
        return view('property.lead.index');
    }

    public function create()
    {
        return view('property.lead.page', ['lead_id' => null]);
    }

    public function edit($id)
    {
        return view('property.lead.page', ['lead_id' => $id]);
    }

    public function calendar()
    {
        return view('property.lead.calendar');
    }

    public function calendarData(Request $request)
    {
        return (new Calendar())->getData($request);
    }
}
