<?php

namespace App\Http\Controllers;

class IncomeController extends Controller
{
    public function index()
    {
        return view('accounts.income.index');
    }
}
