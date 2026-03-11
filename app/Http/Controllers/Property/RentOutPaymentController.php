<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;

class RentOutPaymentController extends Controller
{
    public function payments($agreementType = 'rental')
    {
        if ($agreementType === 'lease') {
            return view('property.sale.payments');
        }

        return view('property.rent.payments');
    }

    public function utilities()
    {
        return view('property.rent.utilities');
    }

    public function services()
    {
        return view('property.rent.services');
    }

    public function paymentDue($agreementType = 'rental')
    {
        return view('property.rent.payment-due');
    }

    public function chequeManagement($agreementType = 'rental')
    {
        if ($agreementType === 'lease') {
            return view('property.sale.cheque-management');
        }

        return view('property.rent.cheque-management');
    }

    public function paymentHistory($agreementType = 'rental')
    {
        return view('property.rent.payment-history');
    }
}
