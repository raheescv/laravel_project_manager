<?php

namespace App\Http\Controllers\Tailoring;

use App\Actions\Tailoring\Order\CreateTailoringOrderAction;
use App\Actions\Tailoring\Order\DeleteTailoringOrderAction;
use App\Actions\Tailoring\Order\GetOrderByOrderNumberAction;
use App\Actions\Tailoring\Order\GetTailoringOrderAction;
use App\Actions\Tailoring\Order\Item\AddTailoringItemAction;
use App\Actions\Tailoring\Order\Item\DeleteTailoringItemAction;
use App\Actions\Tailoring\Order\Item\UpdateTailoringItemAction;
use App\Actions\Tailoring\Order\SubmitOrderCompletionAction;
use App\Actions\Tailoring\Order\UpdateOrderCompletionAction;
use App\Actions\Tailoring\Order\UpdateTailoringOrderAction;
use App\Actions\Tailoring\Payment\CreateAction as PaymentCreateAction;
use App\Actions\Tailoring\Payment\DeleteAction as PaymentDeleteAction;
use App\Actions\Tailoring\Payment\UpdateAction as PaymentUpdateAction;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Configuration;
use App\Models\Country;
use App\Models\CustomerType;
use App\Models\Product;
use App\Models\Rack;
use App\Models\TailoringCategory;
use App\Models\TailoringCategoryMeasurement;
use App\Models\TailoringCategoryModel;
use App\Models\TailoringMeasurementOption;
use App\Models\TailoringOrder;
use App\Models\TailoringOrderItem;
use App\Models\TailoringOrderMeasurement;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class OrderController extends Controller
{
    // Web Routes
    public function index(Request $request)
    {
        return view('tailoring.index');
    }

    public function receiptsPage()
    {
        return view('tailoring.receipts');
    }

    public function create()
    {
        return $this->page();
    }

    public function page($id = null)
    {
        $categories = TailoringCategory::with(['activeModels:id,tailoring_category_id,name', 'activeMeasurements:id,tailoring_category_id,field_key,label,field_type,options_source,section,sort_order'])->active()->ordered()->get();
        $measurementOptions = $this->getMeasurementOptions();
        $salesmen = User::employee()->pluck('name', 'id')->toArray();

        // Get small list of recent customers for initial view
        $customers = Account::customer()
            ->orderBy('id', 'desc')
            ->limit(1)
            ->get(['id', 'name', 'mobile'])
            ->map(function ($c) {
                return [
                    'id' => $c->id,
                    'name' => $c->name,
                    'mobile' => $c->mobile,
                    'label' => $c->name.($c->mobile ? " ({$c->mobile})" : ''),
                ];
            })
            ->keyBy('id')
            ->toArray();

        $orderData = [
            'id' => null,
            'order_no' => '',
            'order_date' => date('Y-m-d'),
            'delivery_date' => date('Y-m-d', strtotime('+7 days')),
            'customer_id' => null,
            'customer_name' => '',
            'customer_mobile' => '',
            'salesman_id' => null,
            'items' => [],
            'payments' => [],
        ];

        if ($id) {
            $action = new GetTailoringOrderAction();
            $result = $action->execute($id);
            if ($result['success']) {
                $orderData = $result['data'];
            }
        }
        $paymentMethodIds = cache('payment_methods', []);
        $paymentMethods = Account::whereIn('id', $paymentMethodIds)->get(['id', 'name'])->toArray();

        // Get customer types and countries for CustomerModal
        $customerTypes = CustomerType::pluck('name', 'id')->toArray();

        $countries = Country::pluck('name', 'name')->toArray();
        $tailoringRedirectionPage = Configuration::where('key', 'tailoring_redirection_page')->value('value') ?? 'create';
        if ($id) {
            $tailoringRedirectionPage = 'show';
        }
        $data = [
            'order' => $orderData,
            'categories' => $categories,
            'measurementOptions' => $measurementOptions,
            'salesmen' => $salesmen,
            'customers' => $customers,
            'paymentMethods' => $paymentMethods,
            'customerTypes' => $customerTypes,
            'countries' => $countries,
            'tailoringRedirectionPage' => $tailoringRedirectionPage,
        ];

        return Inertia::render('Tailoring/Order', $data);
    }

    private function getMeasurementOptions(): array
    {
        // Get all unique options_source from TailoringCategoryMeasurement
        $types = TailoringCategoryMeasurement::whereNotNull('options_source')
            ->distinct()
            ->pluck('options_source')
            ->toArray();
        $options = [];
        foreach ($types as $type) {
            // Get from dedicated table
            $tableOptions = array_values(TailoringMeasurementOption::getOptionsByType($type));
            // Also get from history if some are missing (only from rows that have this key)
            $historyOptions = TailoringOrderMeasurement::whereNotNull('data')
                ->get(['data'])
                ->pluck('data')
                ->filter(fn ($data) => is_array($data) && isset($data[$type]) && $data[$type] !== null && $data[$type] !== '')
                ->map(fn ($data) => (string) $data[$type])
                ->unique()
                ->values()
                ->toArray();

            $merged = array_unique(array_merge($tableOptions, $historyOptions));
            sort($merged);

            $options[$type] = array_values($merged);
        }

        return $options;
    }

    public function jobCompletionPage()
    {
        $racks = Rack::active()->pluck('name', 'id')->toArray();
        $tailors = User::where('type', 'employee')->pluck('name', 'id')->toArray();
        $cutters = User::where('type', 'employee')->pluck('name', 'id')->toArray();

        $orderNumbers = TailoringOrder::distinct()
            ->pluck('order_no')
            ->toArray();

        return Inertia::render('Tailoring/JobCompletion', [
            'racks' => $racks,
            'tailors' => $tailors,
            'cutters' => $cutters,
            'orderNumbers' => $orderNumbers,
        ]);
    }

    public function show($id)
    {
        return view('tailoring.order.show', compact('id'));
    }

    public function store(Request $request)
    {
        $action = new CreateTailoringOrderAction();
        $result = $action->execute($request->all(), Auth::id());

        if ($result['success']) {
            if ($request->wantsJson() || $request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'data' => [
                        'id' => $result['data']->id,
                    ],
                ]);
            }

            return redirect()->route('tailoring::order::show', $result['data']->id)
                ->with('success', $result['message']);
        }

        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 422);
        }

        return back()->withErrors(['error' => $result['message']]);
    }

    public function update(Request $request, $id)
    {
        $action = new UpdateTailoringOrderAction();
        $result = $action->execute($id, $request->all(), Auth::id());

        if ($result['success']) {
            if ($request->wantsJson() || $request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'data' => [
                        'id' => $id,
                    ],
                ]);
            }

            return redirect()->route('tailoring::order::show', $id)
                ->with('success', $result['message']);
        }

        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 422);
        }

        return back()->withErrors(['error' => $result['message']]);
    }

    public function destroy($id)
    {
        $result = (new DeleteTailoringOrderAction())->execute((int) $id, (int) Auth::id());

        if (! $result['success']) {
            return redirect()->route('tailoring::order::index')
                ->with('error', $result['message']);
        }

        return redirect()->route('tailoring::order::index')
            ->with('success', 'Order and all related data removed successfully');
    }

    // API Routes
    public function getCategories(): JsonResponse
    {
        $categories = TailoringCategory::with(['activeModels', 'activeMeasurements'])->active()->ordered()->get();

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    public function getCategoryModels($categoryId): JsonResponse
    {
        $models = TailoringCategoryModel::where('tailoring_category_id', $categoryId)
            ->active()
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json([
            'success' => true,
            'data' => $models,
        ]);
    }

    public function addCategoryModel(Request $request): JsonResponse
    {
        $request->validate([
            'tailoring_category_id' => 'required|exists:tailoring_categories,id',
            'name' => 'required|string|max:255',
        ]);

        $model = TailoringCategoryModel::create([
            'tailoring_category_id' => $request->tailoring_category_id,
            'name' => $request->name,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'data' => $model,
            'message' => 'Category model added successfully',
        ]);
    }

    public function getProducts(Request $request): JsonResponse
    {
        $query = Product::where('is_selling', true);

        if ($request->search) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('barcode', 'LIKE', "%{$search}%")
                    ->orWhere('code', 'LIKE', "%{$search}%");
            });
        }

        $products = $query->limit(50)->get(['id', 'name', 'code', 'barcode', 'mrp']);

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }

    public function getProductByBarcode(Request $request): JsonResponse
    {
        $barcode = $request->query('barcode', $request->barcode);

        $product = Product::where('is_selling', true)
            ->where('barcode', $barcode)
            ->first(['id', 'name', 'code', 'barcode', 'mrp']);

        return response()->json([
            'success' => (bool) $product,
            'data' => $product,
        ]);
    }

    public function getProductColors(Request $request): JsonResponse
    {
        // This can be from products table or a separate colors table
        $colors = Product::whereNotNull('color')
            ->distinct()
            ->pluck('color')
            ->filter()
            ->values();

        return response()->json([
            'success' => true,
            'data' => $colors,
        ]);
    }

    public function getMeasurementOptionsApi(): JsonResponse
    {
        $options = $this->getMeasurementOptions();

        return response()->json([
            'success' => true,
            'data' => $options,
        ]);
    }

    public function getOldMeasurements($accountId, $categoryId): JsonResponse
    {
        $measurements = TailoringOrderMeasurement::query()
            ->where('tailoring_category_id', $categoryId)
            ->whereHas('order', function ($q) use ($accountId) {
                $q->where('account_id', $accountId);
            })
            ->with(['order:id,order_no,order_date', 'categoryModel:id,name'])
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        $seen = [];
        $unique = $measurements->filter(function ($m) use (&$seen) {
            $data = $m->data ?? [];
            ksort($data);
            $signature = json_encode([
                'model_id' => $m->tailoring_category_model_id,
                'notes' => $m->tailoring_notes ?? '',
                'data' => $data,
            ]);
            if (isset($seen[$signature])) {
                return false;
            }
            $seen[$signature] = true;

            return true;
        })->take(20);

        $data = $unique->map(function ($m) {
            $item = [
                'id' => $m->id,
                'order_no' => $m->order?->order_no,
                'order_date' => $m->order?->order_date?->format('Y-m-d'),
                'model_name' => $m->categoryModel?->name ?? 'Standard',
                'tailoring_category_model_id' => $m->tailoring_category_model_id,
                'tailoring_category_model_name' => $m->categoryModel?->name,
                'tailoring_notes' => $m->tailoring_notes,
                'data' => $m->data ?? [],
            ];
            // Merge data keys at top level for frontend compatibility
            if (! empty($m->data) && is_array($m->data)) {
                foreach ($m->data as $k => $v) {
                    if ($v !== null && $v !== '') {
                        $item[$k] = $v;
                    }
                }
            }

            return $item;
        })->values()->toArray();

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function addMeasurementOption(Request $request): JsonResponse
    {
        $validTypes = TailoringCategoryMeasurement::whereNotNull('options_source')
            ->distinct()
            ->pluck('options_source')
            ->toArray();

        // Fallback for safety
        if (empty($validTypes)) {
            $validTypes = ['mar_model', 'cuff', 'cuff_cloth', 'cuff_model', 'collar', 'collar_cloth', 'collar_model', 'fp_model', 'pen', 'side_pt_model', 'stitching', 'button'];
        }

        $request->validate([
            'option_type' => 'required|in:'.implode(',', $validTypes),
            'value' => 'required|string|max:255',
        ]);

        $option = TailoringMeasurementOption::create([
            'option_type' => $request->option_type,
            'value' => $request->value,
        ]);

        return response()->json([
            'success' => true,
            'data' => $option,
            'message' => 'Measurement option added successfully',
        ]);
    }

    public function addItem(Request $request): JsonResponse
    {
        $action = new AddTailoringItemAction();
        $result = $action->execute($request->all(), Auth::id());

        return response()->json($result);
    }

    public function updateItem(Request $request, $id): JsonResponse
    {
        $action = new UpdateTailoringItemAction();
        $result = $action->execute($id, $request->all(), Auth::id());

        return response()->json($result);
    }

    public function removeItem($id): JsonResponse
    {
        $action = new DeleteTailoringItemAction();
        $result = $action->execute($id, Auth::id());

        return response()->json($result);
    }

    public function calculateAmount(Request $request): JsonResponse
    {
        $quantity = (float) ($request->quantity ?? 0);
        $quantityPerItem = (float) ($request->quantity_per_item ?? 1);
        $unitPrice = (float) ($request->unit_price ?? 0);
        $stitchRate = (float) ($request->stitch_rate ?? 0);
        $discount = (float) ($request->discount ?? 0);
        $tax = (float) ($request->tax ?? 0);

        $totalQuantity = $quantity * $quantityPerItem;
        $grossAmount = $unitPrice * $totalQuantity;
        $netAmount = $grossAmount - $discount;
        $taxAmount = ($netAmount * $tax) / 100;
        $total = $netAmount + $taxAmount + ($stitchRate * $quantity);

        return response()->json([
            'success' => true,
            'data' => [
                'gross_amount' => round($grossAmount, 2),
                'net_amount' => round($netAmount, 2),
                'tax_amount' => round($taxAmount, 2),
                'total' => round($total, 2),
            ],
        ]);
    }

    // Payment API Methods
    public function addPayment(Request $request): JsonResponse
    {
        $action = new PaymentCreateAction();
        $result = $action->execute($request->all(), Auth::id());

        return response()->json($result);
    }

    public function updatePayment(Request $request, $id): JsonResponse
    {
        $action = new PaymentUpdateAction();
        $result = $action->execute($id, $request->all(), Auth::id());

        return response()->json($result);
    }

    public function deletePayment($id): JsonResponse
    {
        $action = new PaymentDeleteAction();
        $result = $action->execute($id, Auth::id());

        return response()->json($result);
    }

    public function getPayments($orderId): JsonResponse
    {
        $payments = TailoringOrder::findOrFail($orderId)
            ->payments()
            ->with('paymentMethod:id,name')
            ->orderBy('date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $payments,
        ]);
    }

    public function getItem($orderId, $itemId): JsonResponse
    {
        $action = new GetTailoringOrderAction();
        $result = $action->execute($orderId);

        if (! $result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Order not found',
            ], 404);
        }

        $order = $result['data'];
        $item = $order->items->firstWhere('id', (int) $itemId);

        if (! $item) {
            return response()->json([
                'success' => false,
                'message' => 'Item not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $item,
        ]);
    }

    // Job Completion API Methods
    public function getOrderByOrderNumber($orderNo): JsonResponse
    {
        $action = new GetOrderByOrderNumberAction();
        $result = $action->execute($orderNo);

        return response()->json($result);
    }

    public function searchOrders(Request $request): JsonResponse
    {
        $query = TailoringOrder::query();

        if ($request->order_no) {
            $query->where('order_no', 'like', "%{$request->order_no}%");
        }

        if ($request->customer_name) {
            $query->where('customer_name', 'like', "%{$request->customer_name}%");
        }

        if ($request->customer_mobile) {
            $query->where('customer_mobile', 'like', "%{$request->customer_mobile}%");
        }

        if ($request->from_date) {
            $query->where('order_date', '>=', $request->from_date);
        }

        if ($request->to_date) {
            $query->where('order_date', '<=', $request->to_date);
        }

        if ($request->rack_id) {
            $query->where('rack_id', $request->rack_id);
        }

        if ($request->account_id) {
            $query->where('account_id', $request->account_id);
        }

        $orders = $query->latest()
            ->limit(20)
            ->get(['id', 'order_no', 'customer_name', 'customer_mobile', 'order_date', 'delivery_date', 'status']);

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }

    public function updateCompletion(Request $request, $id): JsonResponse
    {
        $action = new UpdateOrderCompletionAction();
        $result = $action->execute($id, $request->all(), Auth::id());

        return response()->json($result);
    }

    public function submitCompletion(Request $request, $id): JsonResponse
    {
        $action = new SubmitOrderCompletionAction();
        $result = $action->execute($id, $request->all(), Auth::id());

        return response()->json($result);
    }

    public function getRacks(): JsonResponse
    {
        $racks = Rack::active()->orderBy('name')->get(['id', 'name']);

        return response()->json([
            'success' => true,
            'data' => $racks,
        ]);
    }

    public function getTailors(): JsonResponse
    {
        $tailors = User::where('type', 'employee')->orderBy('name')->get(['id', 'name']);

        return response()->json([
            'success' => true,
            'data' => $tailors,
        ]);
    }

    public function getCutters(): JsonResponse
    {
        $cutters = User::where('type', 'employee')->orderBy('name')->get(['id', 'name']);

        return response()->json([
            'success' => true,
            'data' => $cutters,
        ]);
    }

    public function calculateStockBalance(Request $request): JsonResponse
    {
        $stockQuantity = (float) ($request->stock_quantity ?? 0);
        $usedQuantity = (float) ($request->used_quantity ?? 0);
        $wastage = (float) ($request->wastage ?? 0);

        $totalQuantityUsed = $usedQuantity + $wastage;
        $stockBalance = $stockQuantity - $totalQuantityUsed;

        return response()->json([
            'success' => true,
            'data' => [
                'total_quantity_used' => round($totalQuantityUsed, 3),
                'stock_balance' => round($stockBalance, 3),
            ],
        ]);
    }

    public function calculateTailorCommission(Request $request): JsonResponse
    {
        $commission = (float) ($request->tailor_commission ?? 0);
        $quantity = (float) ($request->quantity ?? 0);

        $totalCommission = $commission * $quantity;

        return response()->json([
            'success' => true,
            'data' => [
                'tailor_total_commission' => round($totalCommission, 2),
            ],
        ]);
    }

    public function updateItemCompletion(Request $request, $itemId): JsonResponse
    {
        $item = TailoringOrderItem::findOrFail($itemId);
        $item->updateCompletion($request->all());

        // Reload with relationships and append measurements
        $item = $item->fresh(['category' => fn ($q) => $q->with('activeMeasurements'), 'categoryModel', 'product', 'unit', 'tailor']);
        $order = $item->order()->with(['measurements.category.activeMeasurements'])->first();

        $order->setRelation('items', collect([$item]));
        $order->appendMeasurementsToItems();
        $updatedItem = $order->items->first();

        return response()->json([
            'success' => true,
            'message' => 'Item completion updated successfully',
            'data' => $updatedItem,
        ]);
    }

    public function getProductStock($productId): JsonResponse
    {
        $stock = Product::where('id', $productId)->withSum('inventories as stock_quantity', 'quantity')->first();

        return response()->json([
            'success' => true,
            'data' => [
                'stock_quantity' => $stock ? (float) $stock->stock_quantity : 0,
            ],
        ]);
    }

    public function printCuttingSlip($id, $categoryId, $modelId = 'all')
    {
        $action = new GetTailoringOrderAction();
        $result = $action->execute($id);

        if (! $result['success']) {
            return redirect()->back()->with('error', $result['message']);
        }

        $order = $result['data'];

        // Filter items by category and model
        $filteredItems = $order->items->filter(function ($item) use ($categoryId, $modelId) {
            $catMatch = (string) $item->tailoring_category_id === (string) $categoryId;
            $modelMatch = true;
            if ($modelId !== 'all') {
                $modelMatch = (string) $item->tailoring_category_model_id === (string) $modelId;
            }

            return $catMatch && $modelMatch;
        });

        if ($filteredItems->isEmpty()) {
            return redirect()->back()->with('error', 'No items found for the selected category/model');
        }

        return view('print.tailoring.cutting-slip', [
            'order' => $order,
            'items' => $filteredItems,
            'categoryId' => $categoryId,
            'modelId' => $modelId,
        ]);
    }
}
