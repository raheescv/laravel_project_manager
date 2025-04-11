<?php

namespace App\Http\Controllers;

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
}
