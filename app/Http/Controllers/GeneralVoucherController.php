<?php

namespace App\Http\Controllers;

class GeneralVoucherController extends Controller
{
    public function index()
    {
        return view('accounts.general-voucher.index');
    }
}
