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
        if (cache('sale_type') == 'pos') {
            return redirect()->route('sale::pos', $id);
        }

        return view('sale.page', compact('id'));
    }

    public function posPage($id = null)
    {
        $categories = Category::withCount('products')->get()->toArray();
        $employees = User::employee()->pluck('name', 'id')->toArray();
        $useDefaultCustomer = (Configuration::where('key', 'default_customer_enabled')->value('value') ?? 'yes') === 'yes';
        $customers = [];
        if ($useDefaultCustomer) {
            $customers[3] = [
                'id' => 3,
                'name' => 'General Customer',
                'mobile' => '',
            ];
        }
        $priceTypes = priceTypes();
        $customerTypes = CustomerType::pluck('name', 'id')->toArray();
        $countries = Country::pluck('name', 'name')->toArray();

        // Get payment methods from configuration
        $paymentMethodIds = json_decode(Configuration::where('key', 'payment_methods')->value('value'), true);
        $paymentMethods = [];
        if ($paymentMethodIds) {
            $paymentMethods = Account::whereIn('id', $paymentMethodIds)->get(['name', 'id'])->toArray();
        }

        // Get default product type from configuration
        $defaultProductType = Configuration::where('key', 'default_product_type')->value('value') ?? 'service';

        // Default sale data
        $saleData = [
            'id' => null,
            'employee_id' => '',
            'sale_type' => 'normal',
            'account_id' => $useDefaultCustomer ? 3 : null,
            'account_name' => $useDefaultCustomer ? 'General Customer' : null,
            'customer_mobile' => '',
            'other_discount' => 0,
            'round_off' => 0,
            'total' => 0,
            'grand_total' => 0,
            'items' => [],
            'comboOffers' => [],
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
                            'product:id,name,mrp,size,barcode',
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
                    'comboOffers' => [],
                    'payment_method' => 1, // Default, will be overridden if needed
                    'custom_payment_data' => null,
                ];

                // Transform sale items to match POS item structure (as object with keys)
                $cartItems = [];
                foreach ($sale->items as $item) {
                    $key = $item->employee_id.'-'.$item->inventory_id;
                    $cartItems[$key] = [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'inventory_id' => $item->inventory_id,
                        'name' => $item->product->name,
                        'barcode' => $item->product->barcode,
                        'size' => $item->product->size,
                        'category' => $item->product->category->name ?? 'N/A',
                        'unit_price' => (float) $item->unit_price,
                        'quantity' => (int) $item->quantity,
                        'discount' => (float) ($item->discount ?? 0),
                        'tax' => (float) ($item->tax_percentage ?? 0),
                        'gross_amount' => (float) $item->gross_amount,
                        'net_amount' => (float) $item->net_amount,
                        'tax_amount' => (float) $item->tax_amount,
                        'total' => (float) $item->total,
                        'stock_available' => $item->inventory->quantity ?? 0,
                        'employee_id' => $item->employee_id,
                        'employee_name' => $item->employee->name ?? 'Unknown Employee',
                        'combo_offer_price' => 0,
                        'combo_offer_id' => null,
                    ];
                }
                $saleData['items'] = $cartItems;

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
                        'totalPaid' => $sale->payments->sum('amount'),
                        'balanceDue' => $sale->grand_total - $sale->payments->sum('amount'),
                    ];
                }

                // Handle combo offers and update cart items with combo pricing
                if ($sale->comboOffers->count() > 0) {
                    $comboOffers = [];
                    foreach ($sale->comboOffers as $saleComboOffer) {
                        $comboOfferItems = [];
                        foreach ($saleComboOffer->items as $item) {
                            $key = $item->employee_id.'-'.$item->inventory_id;
                            $comboOfferPrice = (float) ($item->unit_price - $item->discount);
                            $discount = (float) ($item->unit_price - $comboOfferPrice);

                            $comboOfferItems[] = [
                                'key' => $key,
                                'employee_id' => $item->employee_id,
                                'employee_name' => $item->employee->name ?? 'Unknown Employee',
                                'inventory_id' => $item->inventory_id,
                                'product_id' => $item->product_id,
                                'name' => $item->product->name ?? 'Unknown Product',
                                'unit_price' => (float) $item->unit_price,
                                'quantity' => (int) $item->quantity,
                                'discount' => $discount,
                                'tax' => (float) ($item->tax_percentage ?? 0),
                                'gross_amount' => (float) $item->gross_amount,
                                'net_amount' => (float) $item->net_amount,
                                'tax_amount' => (float) $item->tax_amount,
                                'total' => (float) $item->total,
                                'combo_offer_price' => $comboOfferPrice,
                                'combo_offer_id' => $saleComboOffer->combo_offer_id,
                            ];

                            // Update the corresponding cart item with combo offer pricing
                            if (isset($cartItems[$key])) {
                                $cartItems[$key]['combo_offer_price'] = $comboOfferPrice;
                                $cartItems[$key]['discount'] = $discount;
                                $cartItems[$key]['combo_offer_id'] = $saleComboOffer->combo_offer_id;
                            }
                        }

                        $comboOffers[] = [
                            'id' => $saleComboOffer->id,
                            'combo_offer_id' => $saleComboOffer->combo_offer_id,
                            'combo_offer_name' => $saleComboOffer->comboOffer->name,
                            'amount' => (float) $saleComboOffer->amount,
                            'items' => $comboOfferItems,
                        ];
                    }
                    $saleData['comboOffers'] = $comboOffers;
                    $saleData['items'] = $cartItems; // Update with combo pricing
                } else {
                    $saleData['comboOffers'] = [];
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
            'defaultProductType' => $defaultProductType,
            'defaultCustomerEnabled' => $useDefaultCustomer,
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
