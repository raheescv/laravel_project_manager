<?php

namespace App\Http\Controllers;

use App\Models\Account;

class ReportController extends Controller
{
    public function sale_item()
    {
        return view('report.sale_item');
    }

    public function sale_return_item()
    {
        return view('report.sale_return_item');
    }

    public function purchase_item()
    {
        return view('report.purchase_item');
    }

    public function day_book()
    {
        return view('report.day_book');
    }

    public function sale_summary()
    {
        return view('report.sale_summary');
    }

    public function sales_overview()
    {
        return view('report.sales_overview');
    }

    public function customer()
    {
        $countries = Account::pluck('nationality', 'nationality')->toArray();

        return view('report.customer', compact('countries'));
    }

    public function employee()
    {
        return view('report.employee');
    }

    public function product()
    {
        return view('report.product');
    }

    public function ai_generated()
    {
        return view('report.ai_generated');
    }
}
