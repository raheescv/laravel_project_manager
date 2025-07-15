<?php

namespace App\Http\Controllers\Api;

use App\Actions\Sale\CreateAction;
use App\Actions\Sale\Item\DeleteAction as ItemDeleteAction;
use App\Actions\Sale\Payment\DeleteAction as PaymentDeleteAction;
use App\Actions\Sale\UpdateAction;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class POSController extends Controller
{
    public function getProducts(Request $request)
    {
        try {
            $query = Inventory::with(['product']);

            // Filter by category
            if ($request->category_id && $request->category_id !== 'favorite') {
                $query->whereHas('product', function ($q) use ($request) {
                    $q->where('main_category_id', $request->category_id);
                });
            } elseif ($request->category_id === 'favorite') {
                $query->whereHas('product', function ($q) {
                    $q->where('is_favorite', true);
                });
            }

            // Filter by search term
            if ($request->search) {
                $search = $request->search;
                $query->whereHas('product', function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('barcode', 'LIKE', "%{$search}%");
                });
            }

            // Filter by sale type for pricing
            $saleType = $request->sale_type ?? 'normal';

            $products = $query->limit(50)->get()->map(function ($inventory) use ($saleType) {
                $price = $inventory->product->saleTypePrice($saleType);

                $imageUrl = cache('logo');
                if ($inventory->product->thumbnail) {
                    $imageUrl = $inventory->product->thumbnail;
                }

                return [
                    'id' => $inventory->id,
                    'name' => $inventory->product->name,
                    'type' => $inventory->product->type,
                    'barcode' => $inventory->product->barcode,
                    'mrp' => $price,
                    'stock' => $inventory->quantity ?? 0,
                    'category_id' => $inventory->product->main_category_id,
                    'product_id' => $inventory->product_id,
                    'image' => $imageUrl,
                ];
            });

            return response()->json($products);
        } catch (\Exception $e) {
            Log::error('Error loading products: '.$e->getMessage());

            return response()->json(['error' => 'Failed to load products'], 500);
        }
    }

    public function getProductByBarcode(Request $request)
    {
        try {
            $inventory = Inventory::with(['product'])
                ->whereHas('product', function ($q) use ($request) {
                    $q->where('barcode', $request->barcode)
                        ->where('status', 'active');
                })
                ->first();

            if (! $inventory) {
                return response()->json(null, 404);
            }

            $saleType = $request->sale_type ?? 'normal';
            $price = $inventory->product->saleTypePrice($saleType);

            // Get product image URL
            $imageUrl = cache('logo');
            if ($inventory->product->thumbnail) {
                $imageUrl = $inventory->product->thumbnail;
            }

            return response()->json([
                'id' => $inventory->id,
                'name' => $inventory->product->name,
                'type' => $inventory->product->type,
                'barcode' => $inventory->product->barcode,
                'mrp' => $price,
                'stock' => $inventory->quantity ?? 0,
                'category_id' => $inventory->category_id,
                'product_id' => $inventory->product_id,
                'image' => $imageUrl,
            ]);
        } catch (\Exception $e) {
            Log::error('Error finding product by barcode: '.$e->getMessage());

            return response()->json(['error' => 'Failed to find product'], 500);
        }
    }

    public function addItem(Request $request)
    {
        try {
            $request->validate([
                'inventory_id' => 'required|exists:inventories,id',
                'employee_id' => 'required|exists:users,id',
                'sale_type' => 'string',
            ]);

            $inventory = Inventory::with(['product'])->findOrFail($request->inventory_id);
            $saleType = $request->sale_type ?? 'normal';

            // Get pricing based on sale type
            $unitPrice = $inventory->product->saleTypePrice($saleType);

            // Check stock availability
            if ($inventory->quantity <= 0) {
                // return response()->json(['error' => 'Insufficient stock'], 400);
            }

            // Create item data with guaranteed id
            $item = [
                'id' => null,
                'inventory_id' => $inventory->id,
                'product_id' => $inventory->product_id,
                'employee_id' => $request->employee_id,
                'name' => $inventory->product->name,
                'quantity' => 1,
                'unit_price' => $unitPrice,
                'total' => $unitPrice,
                'discount' => 0,
                'tax_amount' => 0,
                'gross_amount' => $unitPrice,
                'employee_name' => User::find($request->employee_id)->name ?? '',
            ];

            return response()->json($item);
        } catch (\Exception $e) {
            Log::error('Error adding item: '.$e->getMessage());

            return response()->json(['error' => 'Failed to add item'], 500);
        }
    }

    public function updateItem(Request $request)
    {
        try {
            $request->validate(['key' => 'required|string', 'item' => 'required|array']);

            $item = $this->calculateItemTotals($request->item);

            return response()->json($item);
        } catch (\Exception $e) {
            Log::error('Error updating item: '.$e->getMessage());

            return response()->json(['error' => 'Failed to update item'], 500);
        }
    }

    public function removeItem(Request $request)
    {
        try {
            $request->validate([
                'key' => 'required|string',
            ]);

            $id = $request->item_id ?? null;
            if ($id) {
                $response = (new ItemDeleteAction())->execute($id);
                if (! $response['success']) {
                    throw new Exception($response['message'], 1);
                }
            }

            return response()->json(['success' => true]);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to remove item'], 500);
        }
    }

    public function submitSale(Request $request)
    {
        try {
            $user_id = Auth::id();
            DB::beginTransaction();

            $saleData = $request->all();
            info($saleData);
            $table_id = $saleData['id'] ?? null;

            if ($saleData['status'] == 'completed') {
                if ($table_id) {
                    $response = $this->removePayment($table_id);
                    if (! $response['success']) {
                        throw new Exception($response['message'], 1);
                    }
                }
                if ($saleData['payment_method'] == 'custom') {
                    $saleData['payments'] = $saleData['custom_payment_data']['payments'];
                    $saleData['paid'] = array_sum(array_column($saleData['payments'], 'amount'));
                } else {
                    $saleData['paid'] = $saleData['grand_total'];
                    $saleData['payments'] = [
                        [
                            'amount' => $saleData['grand_total'],
                            'payment_method_id' => $saleData['payment_method'],
                        ],
                    ];
                }
            } else {
                $saleData['payments'] = [];
            }
            if (! $table_id) {
                $response = (new CreateAction())->execute($saleData, $user_id);
            } else {
                $response = (new UpdateAction())->execute($saleData, $table_id, $user_id);
            }
            if (! $response['success']) {
                throw new Exception($response['message'], 1);
            }
            $sale = $response['data'];
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Sale submitted successfully',
                'sale_id' => $sale->id,
                'redirect' => route('sale::pos'),
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error submitting sale: '.$e->getMessage());

            return response()->json(['error' => 'Failed to submit sales'], 500);
        }
    }

    private function calculateItemTotals($item)
    {
        $unit_price = (float) ($item['unit_price'] ?? 0);
        $quantity = (float) ($item['quantity'] ?? 1);
        $discount = (float) ($item['discount'] ?? 0);
        $tax_rate = (float) ($item['tax'] ?? 0);

        $gross_amount = $unit_price * $quantity;
        $net_amount = $gross_amount - $discount;
        $tax_amount = $net_amount * ($tax_rate / 100);

        $item['gross_amount'] = round($gross_amount, 2);
        $item['net_amount'] = round($net_amount, 2);
        $item['tax_amount'] = round($tax_amount, 2);
        $item['total'] = round($net_amount + $tax_amount, 2);

        return $item;
    }

    public function getDraftSales(Request $request)
    {
        try {
            $drafts = Sale::with(['account:id,name,mobile', 'createdUser:id,name'])
                ->where('status', 'draft')
                ->currentBranch()
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($sale) {
                    return [
                        'id' => $sale->id,
                        'date' => $sale->date,
                        'customer_name' => $sale->account->name ?? 'General Customer',
                        'customer_mobile' => $sale->account->mobile ?? $sale->customer_mobile,
                        'employee_name' => $sale->createdUser->name ?? 'Unknown',
                        'items_count' => $sale->items()->count(),
                        'grand_total' => $sale->grand_total,
                        'created_at' => $sale->created_at->format('Y-m-d H:i:s'),
                    ];
                });

            return response()->json($drafts);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch draft sales',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function removePayment($sale_id)
    {
        try {
            $payments = SalePayment::where('sale_id', $sale_id)->get();
            if ($payments->isNotEmpty()) {
                foreach ($payments as $payment) {
                    $response = (new PaymentDeleteAction())->execute($payment->id);
                    if (! $response['success']) {
                        throw new Exception($response['message'], 1);
                    }
                }
            }
            $return['success'] = true;
            $return['message'] = 'Payment removed successfully';
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = 'Failed to remove payment: '.$th->getMessage();
        }

        return $return;
    }
}
