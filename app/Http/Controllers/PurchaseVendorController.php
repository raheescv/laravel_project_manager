<?php

namespace App\Http\Controllers;

use App\Models\Account;

class PurchaseVendorController extends Controller
{
    public function index()
    {
        return view('purchase-vendor.index');
    }

    public function show($id)
    {
        $vendor = Account::vendor()->findOrFail($id);

        return view('purchase-vendor.view', compact('vendor'));
    }
}
