<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Contracts\View\View;

class PurchaseVendorController extends Controller
{
    public function index(): View
    {
        return view('purchase-vendor.index');
    }

    public function show($id): View
    {
        $vendor = Account::vendor()->findOrFail($id);

        return view('purchase-vendor.view', compact('vendor'));
    }
}
