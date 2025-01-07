<?php

namespace App\Http\Controllers;

class ReportController extends Controller
{
    public function sale_item()
    {
        return view('report.sale_item');
    }

    public function day_book()
    {
        return view('report.day_book');
    }
}
