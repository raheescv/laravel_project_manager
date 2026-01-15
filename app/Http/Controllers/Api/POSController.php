<?php

namespace App\Http\Controllers\Api;
use App\Models\CustomerMeasurement;

use App\Actions\Sale\CreateAction;
use App\Actions\Sale\Item\DeleteAction as ItemDeleteAction;
use App\Actions\Sale\Payment\DeleteAction as PaymentDeleteAction;
use App\Actions\Sale\UpdateAction;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\MeasurementCategory;
use App\Models\Configuration;
use App\Models\Inventory;
use App\Models\Sale;
use App\Models\Product;
use App\Models\SalePayment;
use App\Models\User;
use App\Models\Country;
use App\Models\MeasurementSubCategory;

use App\Models\MeasurementTemplate;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class POSController extends Controller
{
    public function getSubCategories($categoryId)
    {
        // Allow callers to pass multiple category ids via query param `category_ids`
        // (comma separated or array). If provided, use those; otherwise fall back
        // to the route parameter.
        $inputIds = request()->input('category_ids');
        $ids = [];

        if ($inputIds) {
            if (is_array($inputIds)) {
                $ids = array_map('intval', $inputIds);
            } else {
                $ids = array_map('intval', array_filter(array_map('trim', explode(',', $inputIds))));
            }
        } else {
            // route param may itself be a comma-separated list
            if (is_string($categoryId) && strpos($categoryId, ',') !== false) {
                $ids = array_map('intval', array_filter(array_map('trim', explode(',', $categoryId))));
            } else {
                $ids = [(int) $categoryId];
            }
        }

        $subcategories = MeasurementSubCategory::whereIn('measurement_category_id', $ids)
            ->select('id', 'name', 'measurement_category_id')
            ->orderBy('name')
            ->get();

        return response()->json($subcategories);
    }

    public function getProducts(Request $request)
    {
        try {
            $query = Inventory::with(['product']);

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
                        'image' => $imageUrl,
                    ];
                });

            return response()->json($products);
        } catch (\Exception $e) {
            Log::error('Error loading products: '.$e->getMessage());

            return response()->json(['error' => 'Failed to load products'], 500);
        }
    }

    public function getProductsbook(Request $request)
{
    // 🔍 Log incoming request (for debugging)
    Log::info('POS getProducts request', [
        'category_id' => $request->category_id,
        'type'        => $request->type,
        'search'      => $request->search,
    ]);

    try {
        $query = Product::query()
            ->where('is_selling', true);

        /* -------------------- Filters -------------------- */

        // Type filter
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Category filter (accepts main_category_id as array or single value)
        if ($request->filled('main_category_id')) {
            $cat = $request->main_category_id;
            if ($cat !== 'favorite') {
                $query->where('main_category_id', $cat);
            } else {
                $query->where('is_favorite', true);
            }
        }

        // Search filter (case-insensitive, ignore spaces for barcode)
        if ($request->filled('search')) {
            $search = trim($request->search);
            $searchLower = strtolower(str_replace(' ', '', $search));
            $query->where(function ($q) use ($search, $searchLower) {
                $q->whereRaw('LOWER(name) LIKE ?', ["%".strtolower($search)."%"])
                  ->orWhereRaw('REPLACE(LOWER(barcode), " ", "") LIKE ?', ["%{$searchLower}%"]);
            });
        }

        /* -------------------- Fetch & Format -------------------- */

        $products = $query
            ->limit(10000) // Increased limit for larger catalogs
            ->get()
            ->map(function ($product) {

                return [
                    // React POS uses this as inventory_id
                    'id'          => $product->id,
                    'product_id'  => $product->id,

                    'name'        => $product->name,
                    'type'        => $product->type,
                    'barcode'     => $product->barcode,
                    'size'        => $product->size,
                    'code'        => $product->code,

                    // Simple pricing
                    'mrp'         => $product->mrp,

                    // Stock fallback (no inventory table)
                    'stock'       => $product->stock ?? 9999,

                    'category_id' => $product->main_category_id,

                    // Image fallback
                    'image'       => $product->thumbnail ?: cache('logo'),
                ];
            });

        return response()->json($products);

    } catch (\Throwable $e) {
        Log::error('POS product load failed', [
            'error' => $e->getMessage(),
        ]);

        return response()->json([
            'error' => 'Failed to load products'
        ], 500);
    }
}

public function getCustomerMeasurements($customerId, $categoryId)
{
    return CustomerMeasurement::where('customer_id', $customerId)
        ->where('category_id', $categoryId)
        ->get()
        ->mapWithKeys(function ($row) {
            return [
                $row->measurement_template_id => $row->value
            ];
        });
}
 public function getEmployee(Request $request)
{
    try {
        $employees = User::employee()
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return response()->json($employees);
    } catch (\Exception $e) {
        Log::error('Error loading employees: ' . $e->getMessage());
        return response()->json(['error' => 'Failed to load employees'], 500);
    }
}
 public function getEmployeeedit(Request $request)
{
    
   try {
    $employees = User::select('id', 'name')
        ->orderBy('name')
        ->get();

    return response()->json($employees);
} catch (\Exception $e) {
    Log::error('Error loading employees: ' . $e->getMessage());
    return response()->json(['error' => 'Failed to load employees'], 500);
}


}

public function getmeasuremetcategory(Request $request)
{
    try {
        $categories = MeasurementCategory::select('id', 'name')
            ->orderBy('name')
            ->get();

        return response()->json($categories);
    } catch (\Exception $e) {
        \Log::error('Error loading categories: ' . $e->getMessage());
        return response()->json(['error' => 'Failed to load categories'], 500);
    }
}

public function getmaincategory(Request $request)
{
    try {
        $categories = Category::select('id', 'name')
            ->orderBy('name')
            ->get();

        return response()->json($categories);
    } catch (\Exception $e) {
        \Log::error('Error loading categories: ' . $e->getMessage());
        return response()->json(['error' => 'Failed to load categories'], 500);
    }
}

public function getMeasurementTemplates($categoryId)
{
    try {
        $templates = MeasurementTemplate::where('category_id', $categoryId)
            ->select('id', 'name', 'values') // include values field
            ->orderBy('id')
            ->get();

        return response()->json($templates);
    } catch (\Exception $e) {
        \Log::error($e->getMessage());
        return response()->json([], 500);
    }
}


    public function getProductByBarcode(Request $request)
    {
        try {
            $inventory = Inventory::with(['product'])
                ->whereHas('product', function ($q) use ($request): void {
                    $q->where('barcode', $request->barcode)->where('status', 'active');
                })
                ->where('inventories.branch_id', session('branch_id'))
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

            return response()->json(['error' => $e->getMessage()], 500);
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

            // Get tax from product
            $taxRate = $inventory->product->tax ?? 0;

            // Check stock availability
            if ($inventory->quantity <= 0) {
                // return response()->json(['error' => 'Insufficient stock'], 400);
            }

            // Get default quantity from sale configuration
            $quantity = (float) (Configuration::where('key', 'default_quantity')->value('value') ?? '0.001');

            // Calculate initial totals
            $grossAmount = $unitPrice * $quantity;
            $discount = 0;
            $netAmount = $grossAmount - $discount;
            $taxAmount = $netAmount * ($taxRate / 100);
            $total = $netAmount + $taxAmount;

            // Create item data with guaranteed id
            $item = [
                'id' => null,
                'inventory_id' => $inventory->id,
                'product_id' => $inventory->product_id,
                'employee_id' => $request->employee_id,
                'name' => $inventory->product->name,
                'barcode' => $inventory->product->barcode,
                'size' => $inventory->product->size,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'tax' => $taxRate,
                'discount' => $discount,
                'gross_amount' => $grossAmount,
                'net_amount' => $netAmount,
                'tax_amount' => $taxAmount,
                'total' => $total,
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
            return response()->json(['error' => 'Failed to remove item : '.$e->getMessage()], 500);
        }
    }

   public function submitSale(Request $request)
{
      Log::info('REQUEST DATA', $request->all());
    try {
        $user_id = Auth::id();
        DB::beginTransaction();

        $saleData = $request->all();
        Log::info('Sale Data:', $saleData);

        $table_id = $saleData['id'] ?? null;
        $saleData['type'] = $saleData['type'] ?? 'sale';

        $saleData['service_charge'] = $request->input('service_charge', 0);

        // Accept either single id, comma-separated string or an array for categories/sub-categories.
        $inputCategoryIds = $request->input('category_ids', $request->input('category_id', null));
        if (is_array($inputCategoryIds)) {
            $saleData['category_ids'] = array_map('intval', $inputCategoryIds);
            $saleData['category_id'] = implode(',', array_filter($saleData['category_ids']));
        } elseif (is_string($inputCategoryIds) && strpos($inputCategoryIds, ',') !== false) {
            $saleData['category_ids'] = array_map('intval', array_filter(array_map('trim', explode(',', $inputCategoryIds))));
            $saleData['category_id'] = $inputCategoryIds; // keep as string of ids
        } else {
            $saleData['category_ids'] = $inputCategoryIds ? [(int)$inputCategoryIds] : [];
            $saleData['category_id'] = $inputCategoryIds !== null ? (string) $inputCategoryIds : null;
        }

        $inputSubCategoryIds = $request->input('sub_category_ids', $request->input('sub_category_id', null));
        if (is_array($inputSubCategoryIds)) {
            $saleData['sub_category_ids'] = array_map('intval', $inputSubCategoryIds);
            $saleData['sub_category_id'] = implode(',', array_filter($saleData['sub_category_ids']));
        } elseif (is_string($inputSubCategoryIds) && strpos($inputSubCategoryIds, ',') !== false) {
            $saleData['sub_category_ids'] = array_map('intval', array_filter(array_map('trim', explode(',', $inputSubCategoryIds))));
            $saleData['sub_category_id'] = $inputSubCategoryIds;
        } else {
            $saleData['sub_category_ids'] = $inputSubCategoryIds ? [(int)$inputSubCategoryIds] : [];
            $saleData['sub_category_id'] = $inputSubCategoryIds !== null ? (string) $inputSubCategoryIds : null;
        }
       // Normalize width inputs: accept array or comma-separated string and store as imploded CSV
       $inputWidths = $request->input('width', $request->input('widths', ''));
       if (is_array($inputWidths)) {
           $widths = array_map('trim', $inputWidths);
           $saleData['widths'] = $widths;
           $saleData['width'] = implode(',', array_filter($widths, function($v){ return $v !== null && $v !== ''; }));
       } elseif (is_string($inputWidths) && strpos($inputWidths, ',') !== false) {
           $parts = array_map('trim', explode(',', $inputWidths));
           $saleData['widths'] = $parts;
           $saleData['width'] = $inputWidths;
       } else {
           $saleData['widths'] = $inputWidths !== null ? [ (string) $inputWidths ] : [];
           $saleData['width'] = $inputWidths !== null ? (string) $inputWidths : '';
       }

       // Normalize size inputs similarly
       $inputSizes = $request->input('size', $request->input('sizes', ''));
       if (is_array($inputSizes)) {
           $sizes = array_map('trim', $inputSizes);
           $saleData['sizes'] = $sizes;
           $saleData['size'] = implode(',', array_filter($sizes, function($v){ return $v !== null && $v !== ''; }));
       } elseif (is_string($inputSizes) && strpos($inputSizes, ',') !== false) {
           $parts = array_map('trim', explode(',', $inputSizes));
           $saleData['sizes'] = $parts;
           $saleData['size'] = $inputSizes;
       } else {
           $saleData['sizes'] = $inputSizes !== null ? [ (string) $inputSizes ] : [];
           $saleData['size'] = $inputSizes !== null ? (string) $inputSizes : '';
       }
       


        /* ---------------- Existing payment logic ---------------- */
        if ($saleData['status'] == "draft" || $saleData['status'] == "completed") {
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

        /* ---------------- Create / Update Sale ---------------- */
        if (! $table_id) {
            $response = (new CreateAction())->execute($saleData, $user_id);
        } else {
            $response = (new UpdateAction())->execute($saleData, $table_id, $user_id);
        }

        if (! $response['success']) {
            throw new Exception($response['message'], 1);
        }

        $sale = $response['data'];

        /* ======================================================
           ✅ SAVE CUSTOMER MEASUREMENTS (supports multi-category)
        ====================================================== */
        if (!empty($saleData['measurements'])) {
            CustomerMeasurement::where('sale_id', $sale->id)->delete();
            foreach ($saleData['measurements'] as $m) {
                if (empty($m['value'])) {
                    continue;
                }
                // Determine category for this measurement. Prefer explicit measurement category
                // sent from client (`category_id` on measurement). Fallback to template->category_id
                // and finally to the first selected category in the sale.
                $mCategoryId = $m['category_id'] ?? null;
                if (empty($mCategoryId) && !empty($m['measurement_template_id'])) {
                    $template = MeasurementTemplate::find($m['measurement_template_id']);
                    $mCategoryId = $template->category_id ?? null;
                }
                if (empty($mCategoryId) && !empty($saleData['category_ids']) && is_array($saleData['category_ids'])) {
                    $mCategoryId = $saleData['category_ids'][0] ?? null;
                }
                $mSubCategoryId = $m['sub_category_id'] ?? null;
                $mSize = $m['size'] ?? null;
                $mWidth = $m['width'] ?? null;
                $mQuantity = $m['quantity'] ?? 1;
                CustomerMeasurement::create([
                    'sale_id' => $sale->id,
                    'customer_id' => $sale->account_id,
                    'category_id' => $mCategoryId,
                    'sub_category_id' => $mSubCategoryId,
                    'measurement_template_id' => $m['measurement_template_id'],
                    'value' => $m['value'],
                    'size' => $mSize,
                    'width' => $mWidth,
                    'quantity' => $mQuantity,
                    'created_by' => $user_id,
                ]);
            }
        }
        /* ================= END SAVE ================= */

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

        return response()->json([
            'error' => 'Failed to submit sales : '.$e->getMessage()
        ], 500);
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

    public function getCountries()
    {
        $countries = Country::select('name', 'code')->get(); // select name and code
        return response()->json([
            'success' => true,
            'countries' => $countries,
        ]);
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

    public function getCustomerMeasurementSale($saleId)
{
    // Return each measurement row only once, with its quantity
    $rows = CustomerMeasurement::where('sale_id', $saleId)->get();
    return response()->json($rows);
}

}