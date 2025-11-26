<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;
use App\Models\Sale;

class AccountController extends Controller
{
    public function index()
    {
        return view('accounts.index');
    }

    public function customer($id = null)
    {
        if ($id) {
            return view('accounts.customer_details', compact('id'));
        } else {
            return view('accounts.customer');
        }
    }

    public function vendor()
    {
        return view('accounts.vendor');
    }

    public function notes($id = null)
    {
        return view('accounts.notes', compact('id'));
    }

    public function get(Request $request)
    {
        $list = (new Account())->getDropDownList($request->all());

        return response()->json($list);
    }

    public function view($id)
    {
        $account = Account::findOrFail($id);

        return view('accounts.view', compact('id', 'account'));
    }

    public function getCustomerDetails($id)
    {
        try {
            $customer = Account::with('customerType')->findOrFail($id);

            // Get sales statistics
            $totalSales = Sale::where('account_id', $id)->count();
            $totalAmount = Sale::where('account_id', $id)->sum('grand_total');
            $lastPurchase = Sale::where('account_id', $id)
                ->orderBy('date', 'desc')
                ->value('date');

            // Get recent sales (last 5)
            $recentSales = Sale::where('account_id', $id)
                ->select('id', 'invoice_no', 'date', 'grand_total', 'status')
                ->withCount('items')
                ->orderBy('date', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($sale) {
                    return [
                        'id' => $sale->id,
                        'invoice_no' => $sale->invoice_no,
                        'date' => $sale->date,
                        'total' => $sale->grand_total,
                        'status' => $sale->status,
                        'items_count' => $sale->items_count,
                    ];
                });

            return response()->json([
                'success' => true,
                'customer' => $customer,
                'total_sales' => $totalSales,
                'total_amount' => $totalAmount,
                'last_purchase' => $lastPurchase,
                'recent_sales' => $recentSales,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found',
            ], 404);
        }
    }
}
