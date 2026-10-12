<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\Livewire\Property\PropertyLead\Calendar;
use App\Models\PropertyLead;
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
        // 404 rather than a render error when the lead is outside the salesman's team.
        abort_unless(PropertyLead::whereKey($id)->exists(), 404);

        return view('property.lead.page', ['lead_id' => $id]);
    }

    public function calendar()
    {
        return view('property.lead.calendar');
    }

    public function board()
    {
        return view('property.lead.board');
    }

    public function calendarData(Request $request)
    {
        return (new Calendar())->getData($request);
    }
}
