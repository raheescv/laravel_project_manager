<?php

namespace App\Http\Controllers;

class PurchaseReturnController extends Controller
{
    public function index()
    {
        return view('purchase-return.index');
    }

    public function page($id = null)
    {
        return view('purchase-return.page', compact('id'));
    }

    public function view($id)
    {
        return view('purchase-return.view', compact('id'));
    }

    public function payments()
    {
        return view('purchase-return.payments');
    }
}
