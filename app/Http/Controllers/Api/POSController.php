<?php

namespace App\Http\Controllers\Api;

use App\Actions\Sale\CreateAction;
use App\Actions\Sale\Item\DeleteAction as ItemDeleteAction;
use App\Actions\Sale\Payment\DeleteAction as PaymentDeleteAction;
use App\Actions\Sale\Pos\AddItemAction;
use App\Actions\Sale\Pos\GetProductByBarcodeAction;
use App\Actions\Sale\UpdateAction;
use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\Sale;
use App\Models\SalePayment;
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

            $query = $query->whereNull('inventories.employee_id');
            $query = $query->where('inventories.branch_id', session('branch_id'));

            $query->whereHas('product', function ($q): void {
                $q->where('is_selling', true)
                    ->whereHas('mainCategory', function ($categoryQuery): void {
                        $categoryQuery->where('sale_visibility_flag', true);
                    });
            });
            // Filter by category
            if ($request->type) {
                $query->whereHas('product', function ($q) use ($request): void {
                    $q->where('type', $request->type);
                });
            }
            if ($request->category_id && $request->category_id !== 'favorite') {
                $query->whereHas('product', function ($q) use ($request): void {
                    $q->where('main_category_id', $request->category_id);
                });
            } elseif ($request->category_id === 'favorite') {
                $query->whereHas('product', function ($q): void {
                    $q->where('is_favorite', true);
                });
            }

            // Filter by search term
            if ($request->search) {
                $search = trim($request->search);
                $query->whereHas('product', function ($q) use ($search): void {
                    $q->where('name', 'LIKE', "%{$search}%")->orWhere('barcode', 'LIKE', "%{$search}%");
                });
            }

            // Filter by sale type for pricing
            $saleType = $request->sale_type ?? 'normal';

            $products = $query
                ->limit(50)
                ->get()
                ->map(function ($inventory) use ($saleType) {
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
                        'size' => $inventory->product->size,
                        'code' => $inventory->product->code,
                        'mrp' => $price,
                        'stock' => $inventory->quantity ?? 0,
                        'category_id' => $inventory->product->main_category_id,
                        'product_id' => $inventory->product_id,
                        'branch_id' => $inventory->branch_id,
                        'image' => $imageUrl,
                        'unit_id' => $inventory->product->unit_id,
                        'unit_name' => $inventory->product->unit->name ?? '',
                        'conversion_factor' => 1,
                        'units' => collect([
                            [
                                'id' => $inventory->product->unit_id,
                                'name' => $inventory->product->unit->name ?? '',
                                'conversion_factor' => 1,
                            ],
                        ])->concat($inventory->product->units->map(function ($pu) {
                            return [
                                'id' => $pu->sub_unit_id,
                                'name' => $pu->subUnit->name ?? '',
                                'conversion_factor' => $pu->conversion_factor,
                            ];
                        })),
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
        $action = new GetProductByBarcodeAction();
        $result = $action->execute($request->barcode, $request->sale_type ?? 'normal', session('branch_id'));

        if (! $result['success']) {
            return response()->json($result['data'] ?? ['error' => $result['error'] ?? 'Product not found'], $result['status'] ?? 404);
        }

        return response()->json($result['data']);
    }

    public function addItem(Request $request)
    {
        try {
            $request->validate([
                'inventory_id' => 'required|exists:inventories,id',
                'employee_id' => 'required|exists:users,id',
                'sale_type' => 'string',
                'unit_id' => 'nullable|exists:units,id',
            ]);

            $action = new AddItemAction();
            $result = $action->execute($request->inventory_id, $request->employee_id, $request->sale_type ?? 'normal', $request->unit_id);

            if (! $result['success']) {
                return response()->json(['error' => $result['error'] ?? 'Failed to add item'], 500);
            }

            return response()->json($result['data']);
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
            return response()->json(['error' => 'Failed to remove item : '.$e->getMessage()], 500);
        }
    }

    public function submitSale(Request $request)
    {
        try {
            $user_id = Auth::id();
            DB::beginTransaction();

            $saleData = $request->all();
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
                } elseif ($saleData['payment_method'] && $saleData['payment_method'] != 'credit') {
                    $saleData['paid'] = $saleData['grand_total'];
                    $saleData['payments'] = [
                        [
                            'amount' => $saleData['grand_total'],
                            'payment_method_id' => $saleData['payment_method'],
                        ],
                    ];
                } else {
                    $saleData['payments'] = [];
                }
            } else {
                $saleData['payments'] = [];
            }

            $saleData['payments'] = array_map(function ($payment) {
                unset($payment['id']);

                return $payment;
            }, $saleData['payments']);

            if (! $table_id) {
                $response = (new CreateAction())->execute($saleData, $user_id);
            } else {
                $response = (new UpdateAction())->execute($saleData, $table_id, $user_id);
            }
            if (! $response['success']) {
                throw new Exception($response['message'], 1);
            }
            $sale = $response['data'];

            if ($saleData['status'] == 'completed') {
                if ($saleData['send_to_whatsapp']) {
                    $this->sendToWhatsapp($sale->id);
                }
            }
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

            return response()->json(['error' => 'Failed to submit sales :'.$e->getMessage()], 500);
        }
    }

    public function sendToWhatsapp($table_id)
    {
        $response = Sale::sendToWhatsapp($table_id);
        if (! $response['success']) {
            Log::error('Error submitting sale: '.$response['message']);
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
