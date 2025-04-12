<?php

namespace App\Http\Controllers;

class SaleReturnController extends Controller
{
    public function index()
    {
        return view('sales-return.index');
    }

    public function page($id = null)
    {
        return view('sales-return.page', compact('id'));
    }

    public function view($id)
    {

        return view('sales-return.view', compact('id'));
    }

    public function payments()
    {
        return view('sales-return.payments');
    }
}
