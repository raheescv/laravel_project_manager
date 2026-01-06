<?php

namespace App\Http\Controllers;

use App\Models\Account;

class ReportController extends Controller
{
    public function sale_item()
    {
        return view('report.sale_item');
    }
    public function sale_booking_item()
    {
        return view('report.sale_booking_item');
    }

    public function sale_return_item()
    {
        return view('report.sale_return_item');
    }

    public function sale_mixed_items()
    {
        return view('report.sale_mixed_items');
    }

    public function purchase_item()
    {
        return view('report.purchase_item');
    }

    public function purchase_return_item()
    {
        return view('report.purchase_return_item');
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

    public function sale_calendar()
    {
        return view('report.sale_calendar');
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

    public function trial_balance()
    {
        return view('report.trial_balance');
    }

    public function balance_sheet()
    {
        return view('report.balance_sheet');
    }

    public function product()
    {
        return view('report.product');
    }

    public function ai_generated()
    {
        return view('report.ai_generated');
    }

    public function profit_loss()
    {
        return view('report.profit_loss');
    }

    public function stock_analysis()
    {
        return view('report.stock-analysis');
    }

    public function employee_productivity()
    {
        return view('report.employee-productivity');
    }

    public function customer_callback_reminder()
    {
        return view('report.customer.customer-callback-reminder');
    }

    public function customer_aging()
    {
        return view('report.customer_aging');
    }

    public function day_wise_sale()
    {
        return view('report.day_wise_sale');
    }

    public function vendor_aging()
    {
        return view('report.vendor_aging');
    }

    public function day_wise_tax_report()
    {
        return view('report.day_wise_tax_report');
    }

    public function tax_report()
    {
        return view('report.tax_report');
    }
}
