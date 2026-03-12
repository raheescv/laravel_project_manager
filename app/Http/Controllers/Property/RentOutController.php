<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\Support\RentOutConfig;
use Illuminate\Http\Request;

class RentOutController extends Controller
{
    /**
     * Infer agreement type from route name prefix.
     * Routes named property::rent::* are rental, property::sale::* are lease.
     */
    protected function getConfig(Request $request): RentOutConfig
    {
        $routeName = $request->route()->getName() ?? '';
        $type = str_contains($routeName, 'property::rent::') ? 'rental' : 'lease';

        return RentOutConfig::make($type);
    }

    public function index(Request $request)
    {
        $config = $this->getConfig($request);

        return view('property.rent-out.index', compact('config'));
    }

    public function create(Request $request, $id = null)
    {
        $config = $this->getConfig($request);

        return view('property.rent-out.create', compact('config', 'id'));
    }

    public function view(Request $request, $id)
    {
        $config = $this->getConfig($request);

        return view('property.rent-out.view', compact('config', 'id'));
    }

    public function booking(Request $request)
    {
        $config = $this->getConfig($request);

        return view('property.rent-out.booking', compact('config'));
    }

    public function bookingCreate(Request $request, $id = null)
    {
        $config = $this->getConfig($request);

        return view('property.rent-out.booking-create', compact('config', 'id'));
    }

    public function bookingView(Request $request, $id)
    {
        $config = $this->getConfig($request);

        return view('property.rent-out.booking-view', compact('config', 'id'));
    }
}
