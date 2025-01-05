<?php

namespace App\Http\Controllers;

class AccountController extends Controller
{
    public function index()
    {
        return view('accounts.index');
    }

    public function customer()
    {
        return view('accounts.customer');
    }
}
