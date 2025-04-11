<?php

namespace App\Http\Controllers;

class SaleReturnController extends Controller
{
    public function index()
    {
        return view('sale_return.index');
    }

    public function page($id = null)
    {
        return view('sale_return.page', compact('id'));
    }

    public function view($id)
    {

        return view('sale_return.view', compact('id'));
    }

    public function payments()
    {
        return view('sale_return.payments');
    }
}
