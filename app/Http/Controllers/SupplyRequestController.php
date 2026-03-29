<?php

namespace App\Http\Controllers;

use App\Models\RentOut;
use App\Models\SupplyRequest;
use Spatie\Browsershot\Browsershot;

class SupplyRequestController extends Controller
{
    public function index()
    {
        $type = request()->route()->defaults['type'] ?? request('type', 'Add');

        return view('supply-request.index', ['type' => $type]);
    }

    public function create()
    {
        $type = request()->route()->defaults['type'] ?? request('type', 'Add');

        return view('supply-request.create', ['id' => null, 'type' => $type]);
    }

    public function edit($id)
    {
        $model = SupplyRequest::findOrFail($id);

        return view('supply-request.create', ['id' => $id, 'type' => $model->type ?? 'Add']);
    }

    public function print($id, $mode = 'Invoice')
    {
        $supplyRequest = SupplyRequest::with([
            'items.product',
            'items.branch',
            'property.building.group',
            'property.type',
            'creator',
            'approver',
            'accountant',
            'finalApprover',
            'completer',
            'updater',
        ])->findOrFail($id);

        $rentout = null;
        if ($supplyRequest->property_id) {
            $rentout = RentOut::where('property_id', $supplyRequest->property_id)
                ->where('status', 'occupied')
                ->first();
        }

        $companyLogo = cache('logo', asset('assets/img/logo.svg'));
        $companyName = cache('company_name', config('app.name'));

        $html = view('supply-request.print', compact(
            'supplyRequest',
            'rentout',
            'companyLogo',
            'companyName',
            'mode'
        ))->render();

        $pdf = Browsershot::html($html)
            ->format('A4')
            ->margins(15, 15, 15, 15)
            ->showBackground()
            ->pdf();

        return response($pdf)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="supply-request-'.$mode.'-'.time().'.pdf"');
    }
}
