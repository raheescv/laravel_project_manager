<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Category;
use App\Models\Configuration;
use App\Models\Country;
use App\Models\CustomerType;
use App\Models\Sale;
use App\Models\SaleDaySession;
use App\Models\User;
use Illuminate\Http\Request;

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

    public function posPage()
    {
        $categories = Category::withCount('products')->get()->toArray();
        $employees = User::employee()->pluck('name', 'id')->toArray();
        $customers = [];
        $priceTypes = priceTypes();
        $customerTypes = CustomerType::pluck('name', 'id')->toArray();
        $countries = Country::pluck('name', 'name')->toArray();

        // Get payment methods from configuration
        $paymentMethodIds = json_decode(Configuration::where('key', 'payment_methods')->value('value'), true);
        $paymentMethods = [];
        if ($paymentMethodIds) {
            $paymentMethods = Account::whereIn('id', $paymentMethodIds)->get(['name', 'id'])->toArray();
        }
        $saleData = [
            'employee_id' => '',
            'sale_type' => 'normal',
            'account_id' => 3,
            'account_name' => 'General Customer',
            'customer_mobile' => '',
            'other_discount' => 0,
            'round_off' => 0,
            'total' => 0,
            'grand_total' => 0,
            'items' => [],
            'payment_method' => 'cash',
        ];
        $data = [
            'categories' => $categories,
            'employees' => $employees,
            'customers' => $customers,
            'priceTypes' => $priceTypes,
            'customerTypes' => $customerTypes,
            'countries' => $countries,
            'paymentMethods' => $paymentMethods,
            'saleData' => $saleData,
        ];

        return inertia('Sale/POS', $data);
    }

    public function view($id)
    {
        return view('sale.view', compact('id'));
    }

    public function receipts()
    {
        return view('sale.receipts');
    }

    public function get(Request $request)
    {
        $list = (new Sale())->getDropDownList($request->all());

        return response()->json($list);
    }

    public function dayManagement(Request $request)
    {
        return view('sale.day-management');
    }

    public function daySession($id)
    {
        $session = SaleDaySession::with(['branch', 'opener', 'closer'])->findOrFail($id);

        return view('sale.day-session-details', compact('session'));
    }

    public function daySessionsReport()
    {
        return view('sale.day-sessions-report');
    }
}
