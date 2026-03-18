<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;

class RentOutTransactionController extends Controller
{
    public function payments($agreementType = 'rental')
    {
        $title = $agreementType === 'lease' ? 'Sale Payments' : 'Rental Payments';
        $subtitle = $agreementType === 'lease' ? 'Manage sale payments' : 'Manage rental payments';
        $breadcrumb = $agreementType === 'lease' ? 'sale' : 'rental';

        return view('property.rent-out.payments', compact('agreementType', 'title', 'subtitle', 'breadcrumb'));
    }

    public function utilities()
    {
        return view('property.rent-out.utilities');
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
        $title = $agreementType === 'lease' ? 'Sale Cheque Management' : 'Cheque Management';
        $subtitle = $agreementType === 'lease' ? 'Manage cheques for sale agreements' : 'Manage cheques for rental agreements';
        $breadcrumb = $agreementType === 'lease' ? 'sale' : 'rental';

        return view('property.rent-out.cheque-management', compact('agreementType', 'title', 'subtitle', 'breadcrumb'));
    }

    public function paymentHistory($agreementType = 'rental')
    {
        return view('property.rent.payment-history');
    }
}
