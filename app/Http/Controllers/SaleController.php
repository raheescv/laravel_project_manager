<?php

namespace App\Http\Controllers;

class SaleController extends Controller
{
    public function index()
    {
        return view('sale.index');
    }

    public function page($id = null)
    {
        return view('sale.page', compact('id'));
    }

    public function receipts()
    {
        return view('sale.receipts');
    }
}
