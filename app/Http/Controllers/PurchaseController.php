<?php

namespace App\Http\Controllers;

class PurchaseController extends Controller
{
    public function index()
    {
        return view('purchase.index');
    }

    public function page($id = null)
    {
        return view('purchase.page', compact('id'));
    }
}
