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

    public function posPage($id = null)
    {
        $categories = Category::withCount('products')->get()->toArray();
        $employees = User::employee()->pluck('name', 'id')->toArray();
        $customers = [
            3 => [
                'id' => 3,
                'name' => 'General Customer',
                'mobile' => '',
            ],
        ];
        $priceTypes = priceTypes();
        $customerTypes = CustomerType::pluck('name', 'id')->toArray();
        $countries = Country::pluck('name', 'name')->toArray();

        // Get payment methods from configuration
        $paymentMethodIds = json_decode(Configuration::where('key', 'payment_methods')->value('value'), true);
        $paymentMethods = [];
        if ($paymentMethodIds) {
            $paymentMethods = Account::whereIn('id', $paymentMethodIds)->get(['name', 'id'])->toArray();
        }

        // Default sale data
        $saleData = [
            'id' => null,
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
            'payment_method' => 1,
            'custom_payment_data' => null,
            'status' => null,
        ];

        // If ID is provided, load the sale data
        if ($id) {
            try {
                $sale = Sale::with([
                    'account:id,name,mobile',
                    'branch:id,name',
                    'items' => function ($query) {
                        $query->with([
                            'product:id,name,mrp',
                            'employee:id,name',
                            'assistant:id,name',
                        ]);
                    },
                    'comboOffers.comboOffer:id,name',
                    'createdUser:id,name',
                    'updatedUser:id,name',
                    'cancelledUser:id,name',
                    'payments.paymentMethod:id,name',
                ])->findOrFail($id);

                // Add the sale's customer to the customers array if it exists
                if ($sale->account && $sale->account_id !== 3) {
                    $customers[$sale->account_id] = [
                        'id' => $sale->account->id,
                        'name' => $sale->account->name,
                        'mobile' => $sale->account->mobile ?? $sale->customer_mobile ?? '',
                    ];
                } elseif ($sale->account_id && $sale->account_id !== 3 && $sale->customer_name) {
                    // Handle case where account relation doesn't exist but we have customer data
                    $customers[$sale->account_id] = [
                        'id' => $sale->account_id,
                        'name' => $sale->customer_name,
                        'mobile' => $sale->customer_mobile ?? '',
                    ];
                }
                // Transform the sale data to match POS form structure
                $saleData = [
                    'id' => $sale->id,
                    'invoice_no' => $sale->invoice_no,
                    'reference_no' => $sale->reference_no,
                    'sale_type' => $sale->sale_type,
                    'employee_id' => $sale->created_by,
                    'account_id' => $sale->account_id,
                    'account_name' => $sale->account ? $sale->account->name : $sale->customer_name,
                    'customer_mobile' => $sale->customer_mobile,
                    'date' => $sale->date,
                    'due_date' => $sale->due_date,
                    'gross_amount' => $sale->gross_amount,
                    'item_discount' => $sale->item_discount,
                    'tax_amount' => $sale->tax_amount,
                    'other_discount' => $sale->other_discount,
                    'freight' => $sale->freight,
                    'round_off' => $sale->round_off,
                    'total' => $sale->net_amount,
                    'grand_total' => $sale->grand_total,
                    'status' => $sale->status,
                    'items' => [],
                    'payment_method' => 1, // Default, will be overridden if needed
                    'custom_payment_data' => null,
                ];

                // Transform sale items to match POS item structure
                foreach ($sale->items as $item) {
                    $posItem = [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'inventory_id' => $item->inventory_id,
                        'name' => $item->product->name,
                        'barcode' => $item->product->barcode,
                        'category' => $item->product->category->name ?? 'N/A',
                        'unit_price' => $item->unit_price,
                        'quantity' => $item->quantity,
                        'discount' => $item->discount,
                        'tax' => $item->tax_percentage,
                        'gross_amount' => $item->gross_amount,
                        'net_amount' => $item->net_amount,
                        'tax_amount' => $item->tax_amount,
                        'total' => $item->total,
                        'stock_available' => $item->inventory->quantity ?? 0,
                        'employee_id' => $sale->created_by,
                        'employee_name' => $employees[$sale->created_by] ?? 'Unknown Employee',
                    ];
                    $saleData['items'][] = $posItem;
                }

                // Handle payment method
                if ($sale->payments->count() === 1) {
                    $payment = $sale->payments->first();
                    $saleData['payment_method'] = $payment->payment_method_id;
                } elseif ($sale->payments->count() > 1) {
                    $saleData['payment_method'] = 'custom';
                    $saleData['custom_payment_data'] = [
                        'payments' => $sale->payments->map(function ($payment) {
                            return [
                                'id' => $payment->id,
                                'amount' => $payment->amount,
                                'payment_method_id' => $payment->payment_method_id,
                                'payment_method_name' => $payment->paymentMethod->name ?? 'Unknown',
                            ];
                        })->toArray(),
                    ];
                }
            } catch (\Exception $e) {
                // If sale not found or error, use default data but show the ID for error handling
                $saleData['id'] = $id;
                $saleData['load_error'] = 'Sale not found or could not be loaded';
            }
        }
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
