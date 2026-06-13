<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\Models\RentOut;

class RentOutChecklistController extends Controller
{
    public function print($id)
    {
        $rentOut = RentOut::with([
            'account',
            'group',
            'building',
            'property',
            'type',
            'checklistLines.item',
            'checklistSignatures',
            'facilityCoordinator',
            'leasingCoordinator',
        ])->findOrFail($id);

        return view('rentout-checklist.print', compact('rentOut'));
    }
}
