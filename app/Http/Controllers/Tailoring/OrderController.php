<?php

namespace App\Http\Controllers\Tailoring;

use App\Actions\Tailoring\Order\CreateTailoringOrderAction;
use App\Actions\Tailoring\Order\DeleteTailoringOrderAction;
use App\Actions\Tailoring\Order\GetOrderByOrderNumberAction;
use App\Actions\Tailoring\Order\GetTailoringOrderAction;
use App\Actions\Tailoring\Order\Item\AddTailoringItemAction;
use App\Actions\Tailoring\Order\Item\DeleteTailoringItemAction;
use App\Actions\Tailoring\Order\Item\UpdateTailoringItemAction;
use App\Actions\Tailoring\Order\SaveItemCompletionAction;
use App\Actions\Tailoring\Order\SaveOrderCompletionAction;
use App\Actions\Tailoring\Order\UpdateTailoringOrderAction;
use App\Actions\Tailoring\Payment\CreateAction as PaymentCreateAction;
use App\Actions\Tailoring\Payment\DeleteAction as PaymentDeleteAction;
use App\Actions\Tailoring\Payment\UpdateAction as PaymentUpdateAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tailoring\PrintTailoringCuttingSlipsRequest;
use App\Models\Account;
use App\Models\Configuration;
use App\Models\Country;
use App\Models\CustomerType;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Rack;
use App\Models\SaleDaySession;
use App\Models\TailoringCategory;
use App\Models\TailoringCategoryMeasurement;
use App\Models\TailoringCategoryModel;
use App\Models\TailoringCategoryModelType;
use App\Models\TailoringMeasurementOption;
use App\Models\TailoringOrder;
use App\Models\TailoringOrderMeasurement;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class OrderController extends Controller
{
    use ApiResponseTrait;

    // Web Routes
    public function index(Request $request)
    {
        return view('tailoring.index');
    }

    public function receiptsPage()
    {
        return view('tailoring.receipts');
    }

    public function orderManagementPage()
    {
        return view('tailoring.order-management');
    }

    public function create()
    {
        return $this->page();
    }

    public function page($id = null)
    {
        $categories = TailoringCategory::with(['activeModels:id,tailoring_category_id,name', 'activeMeasurements:id,tailoring_category_id,field_key,label,field_type,options_source,section,sort_order'])->active()->ordered()->get();
        $measurementOptions = $this->getMeasurementOptions();
        $salesmen = User::employee()->active()->pluck('name', 'id')->toArray();

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

        $openSession = SaleDaySession::getOpenSessionForBranch(session('branch_id'));
        $date = $openSession ? $openSession->opened_at->format('Y-m-d') : date('Y-m-d');

        $orderData = [
            'id' => null,
            'order_no' => '',
            'order_date' => $date,
            'delivery_date' => date('Y-m-d', strtotime($date.' +7 days')),
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
            'canQuickAddMeasurementOption' => Auth::user()?->can('tailoring measurement option.quick add') ?? false,
            'canManageQuickAddConfiguration' => Auth::user()?->can('tailoring measurement option.quick add') ?? false,
            'quickAddEnabledByDefault' => true,
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
        $cutters = $tailors = User::employee()->active()->pluck('name', 'id')->toArray();

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
                $data = ['id' => $result['data']->id];

                return $this->sendSuccess($data, $result['message']);
            }

            return redirect()->route('tailoring::order::show', $result['data']->id)
                ->with('success', $result['message']);
        }

        if ($request->wantsJson() || $request->expectsJson()) {
            return $this->sendError($result['message'], [], 422);
        }

        return back()->withErrors(['error' => $result['message']]);
    }

    public function update(Request $request, $id)
    {
        $action = new UpdateTailoringOrderAction();
        $result = $action->execute($id, $request->all(), Auth::id());

        if ($result['success']) {
            if ($request->wantsJson() || $request->expectsJson()) {
                $data = ['id' => $id];

                return $this->sendSuccess($data, $result['message']);
            }

            return redirect()->route('tailoring::order::show', $id)->with('success', $result['message']);
        }

        if ($request->wantsJson() || $request->expectsJson()) {
            return $this->sendError($result['message'], [], 422);
        }

        return back()->withErrors(['error' => $result['message']]);
    }

    public function destroy($id)
    {
        $result = (new DeleteTailoringOrderAction())->execute((int) $id, (int) Auth::id());

        if (! $result['success']) {
            return redirect()->route('tailoring::order::index')->with('error', $result['message']);
        }

        return redirect()->route('tailoring::order::index')->with('success', 'Order and all related data removed successfully');
    }

    // API Routes
    public function getCategories(): JsonResponse
    {
        $categories = TailoringCategory::with(['activeModels', 'activeMeasurements'])->active()->ordered()->get();

        return $this->sendSuccess($categories);
    }

    public function getCategoryModels($categoryId): JsonResponse
    {
        $models = TailoringCategoryModel::where('tailoring_category_id', $categoryId)
            ->active()
            ->orderBy('name')
            ->get(['id', 'name']);

        return $this->sendSuccess($models);
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

        return $this->sendSuccess($model, 'Category model added successfully');
    }

    public function getCategoryModelTypes($categoryId): JsonResponse
    {
        $types = TailoringCategoryModelType::where('tailoring_category_id', $categoryId)
            ->active()
            ->orderBy('name')
            ->get(['id', 'name']);

        return $this->sendSuccess($types);
    }

    public function addCategoryModelType(Request $request): JsonResponse
    {
        $request->validate([
            'tailoring_category_id' => 'required|exists:tailoring_categories,id',
            'name' => 'required|string|max:255',
        ]);

        $category = TailoringCategory::findOrFail($request->tailoring_category_id);

        $type = TailoringCategoryModelType::create([
            'tenant_id' => $category->tenant_id,
            'tailoring_category_id' => $request->tailoring_category_id,
            'name' => $request->name,
            'is_active' => true,
        ]);

        return $this->sendSuccess($type, 'Category model type added successfully');
    }

    public function getProducts(Request $request): JsonResponse
    {
        $branchId = (int) ($request->query('branch_id') ?: session('branch_id'));

        $query = Inventory::query()
            ->whereNull('inventories.employee_id')
            ->where('inventories.branch_id', $branchId)
            ->join('products', 'products.id', '=', 'inventories.product_id')
            ->where('products.is_selling', true);

        if ($request->search) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('products.name', 'LIKE', "%{$search}%")
                    ->orWhere('inventories.barcode', 'LIKE', "%{$search}%")
                    ->orWhere('products.code', 'LIKE', "%{$search}%");
            });
        }

        $products = $query
            ->orderByDesc('inventories.id')
            ->limit(50)
            ->get([
                'inventories.id as id',
                'inventories.id as inventory_id',
                'products.id as product_id',
                'products.name',
                'products.code',
                'inventories.barcode',
                'inventories.quantity',
                'products.mrp',
            ]);

        return $this->sendSuccess($products);
    }

    public function getProductByBarcode(Request $request): JsonResponse
    {
        $barcode = $request->query('barcode', $request->barcode);
        $branchId = (int) ($request->query('branch_id') ?: session('branch_id'));

        $product = Inventory::query()
            ->whereNull('inventories.employee_id')
            ->where('inventories.branch_id', $branchId)
            ->join('products', 'products.id', '=', 'inventories.product_id')
            ->where('products.is_selling', true)
            ->where(function ($q) use ($barcode) {
                $q->where('inventories.barcode', $barcode)
                    ->orWhere('products.barcode', $barcode);
            })
            ->first([
                'inventories.id as id',
                'inventories.id as inventory_id',
                'products.id as product_id',
                'products.name',
                'products.code',
                'inventories.barcode',
                'products.mrp',
            ]);

        if (! $product) {
            return $this->sendError('Product not found', [], 200);
        }

        return $this->sendSuccess($product);
    }

    public function getProductColors(Request $request): JsonResponse
    {
        // This can be from products table or a separate colors table
        $colors = Product::whereNotNull('color')
            ->distinct()
            ->pluck('color')
            ->filter()
            ->values();

        return $this->sendSuccess($colors);
    }

    public function getMeasurementOptionsApi(): JsonResponse
    {
        $options = $this->getMeasurementOptions();

        return $this->sendSuccess($options);
    }

    public function getOldMeasurements($accountId, $categoryId): JsonResponse
    {
        $measurements = TailoringOrderMeasurement::query()
            ->where('tailoring_category_id', $categoryId)
            ->whereHas('order', function ($q) use ($accountId) {
                $q->where('account_id', $accountId);
            })
            ->with(['order:id,order_no,order_date', 'categoryModel:id,name', 'categoryModelType:id,name'])
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        $seen = [];
        $unique = $measurements->filter(function ($m) use (&$seen) {
            $data = $m->data ?? [];
            ksort($data);
            $signature = json_encode([
                'model_id' => $m->tailoring_category_model_id,
                'model_type_id' => $m->tailoring_category_model_type_id,
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
                'tailoring_category_model_type_id' => $m->tailoring_category_model_type_id,
                'tailoring_category_model_type_name' => $m->categoryModelType?->name,
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

        return $this->sendSuccess($data);
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

        return $this->sendSuccess($option, 'Measurement option added successfully');
    }

    public function addItem(Request $request): JsonResponse
    {
        $action = new AddTailoringItemAction();
        $result = $action->execute($request->all(), Auth::id());

        return $this->respondWithActionResult($result);
    }

    public function updateItem(Request $request, $id): JsonResponse
    {
        $action = new UpdateTailoringItemAction();
        $result = $action->execute($id, $request->all(), Auth::id());

        return $this->respondWithActionResult($result);
    }

    public function removeItem($id): JsonResponse
    {
        $action = new DeleteTailoringItemAction();
        $result = $action->execute($id, Auth::id());

        return $this->respondWithActionResult($result);
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

        $data = [
            'gross_amount' => round($grossAmount, 2),
            'net_amount' => round($netAmount, 2),
            'tax_amount' => round($taxAmount, 2),
            'total' => round($total, 2),
        ];

        return $this->sendSuccess($data);
    }

    // Payment API Methods
    public function addPayment(Request $request): JsonResponse
    {
        $action = new PaymentCreateAction();
        $result = $action->execute($request->all(), Auth::id());

        return $this->respondWithActionResult($result);
    }

    public function updatePayment(Request $request, $id): JsonResponse
    {
        $action = new PaymentUpdateAction();
        $result = $action->execute($id, $request->all(), Auth::id());

        return $this->respondWithActionResult($result);
    }

    public function deletePayment($id): JsonResponse
    {
        $action = new PaymentDeleteAction();
        $result = $action->execute($id, Auth::id());

        return $this->respondWithActionResult($result);
    }

    public function getPayments($orderId): JsonResponse
    {
        $payments = TailoringOrder::findOrFail($orderId)
            ->payments()
            ->with('paymentMethod:id,name')
            ->orderBy('date')
            ->get();

        return $this->sendSuccess($payments);
    }

    public function getItem($orderId, $itemId): JsonResponse
    {
        $action = new GetTailoringOrderAction();
        $result = $action->execute($orderId);

        if (! $result['success']) {
            return $this->sendNotFoundError($result['message'] ?? 'Order not found');
        }

        $order = $result['data'];
        $item = $order->items->firstWhere('id', (int) $itemId);

        if (! $item) {
            return $this->sendNotFoundError('Item not found');
        }

        return $this->sendSuccess($item);
    }

    // Job Completion API Methods
    public function getOrderByOrderNumber($orderNo): JsonResponse
    {
        $action = new GetOrderByOrderNumberAction();
        $result = $action->execute($orderNo);

        return $this->respondWithActionResult($result);
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

        $orders = $query->latest()->limit(20)->get(['id', 'order_no', 'customer_name', 'customer_mobile', 'order_date', 'delivery_date', 'status']);

        return $this->sendSuccess($orders);
    }

    public function updateCompletion(Request $request, $id): JsonResponse
    {
        $action = new SaveOrderCompletionAction();
        // update flow: do not force default completion date
        $result = $action->execute($id, $request->all(), Auth::id(), ['default_completion_date' => false]);

        return $this->respondWithActionResult($result);
    }

    public function updateItemCompletion(Request $request, $itemId): JsonResponse
    {
        $action = new SaveItemCompletionAction();
        $result = $action->execute($itemId, $request->all(), Auth::id());

        return $this->respondWithActionResult($result);
    }

    public function getRacks(): JsonResponse
    {
        $racks = Rack::active()->orderBy('name')->get(['id', 'name']);

        return $this->sendSuccess($racks);
    }

    public function getTailors(): JsonResponse
    {
        $tailors = User::employee()->active()->orderBy('name')->get(['id', 'name']);

        return $this->sendSuccess($tailors);
    }

    public function getCutters(): JsonResponse
    {
        $cutters = User::employee()->active()->orderBy('name')->get(['id', 'name']);

        return $this->sendSuccess($cutters);
    }

    public function calculateStockBalance(Request $request): JsonResponse
    {
        $stockQuantity = (float) ($request->stock_quantity ?? 0);
        $usedQuantity = (float) ($request->used_quantity ?? 0);
        $wastage = (float) ($request->wastage ?? 0);

        $totalQuantityUsed = $usedQuantity + $wastage;
        $stockBalance = $stockQuantity - $totalQuantityUsed;

        $data = [
            'total_quantity_used' => round($totalQuantityUsed, 3),
            'stock_balance' => round($stockBalance, 3),
        ];

        return $this->sendSuccess($data);
    }

    public function calculateTailorCommission(Request $request): JsonResponse
    {
        $commission = (float) ($request->tailor_commission ?? 0);
        $quantity = (float) ($request->quantity ?? 0);

        $totalCommission = $commission * $quantity;
        $data = ['tailor_total_commission' => round($totalCommission, 2)];

        return $this->sendSuccess($data);
    }

    public function getProductStock(Request $request, $productId): JsonResponse
    {
        $branchId = (int) session('branch_id');
        $inventoryId = $request->query('inventory_id');

        $stockQuantity = 0.0;

        if (! empty($inventoryId)) {
            $stockQuantity = (float) Inventory::query()
                ->where('id', $inventoryId)
                ->where('branch_id', $branchId)
                ->value('quantity');
        }

        $data = ['stock_quantity' => $stockQuantity];

        return $this->sendSuccess($data);
    }

    public function printCuttingSlip($id, $categoryId, $modelId = 'all')
    {
        $action = new GetTailoringOrderAction();
        $result = $action->execute($id);

        if (! $result['success']) {
            return redirect()->back()->with('error', $result['message']);
        }

        $slip = $this->buildCuttingSlipForOrder($result['data'], (string) $categoryId, (string) $modelId);

        if ($slip === null) {
            return redirect()->back()->with('error', 'No items found for the selected category/model');
        }

        $this->markCuttingSlipsPrinted(collect([$slip]));

        return view('print.tailoring.cutting-slip', [
            'order' => $slip['order'],
            'items' => $slip['items'],
            'categoryId' => $slip['categoryId'],
            'modelId' => $slip['modelId'],
        ]);
    }

    public function printCuttingSlips(PrintTailoringCuttingSlipsRequest $request)
    {
        $orders = TailoringOrder::query()
            ->whereIn('id', $request->validated('ids'))
            ->with([
                'branch:id,name,location,mobile',
                'account:id,name,mobile',
                'salesman:id,name',
                'cutter:id,name',
                'items' => function ($query): void {
                    $query->with([
                        'category' => function ($q): void {
                            $q->with('activeMeasurements');
                        },
                        'categoryModel:id,name',
                        'categoryModelType:id,name',
                        'product:id,name,barcode',
                        'latestTailorAssignment.tailor:id,name',
                    ])->orderBy('item_no');
                },
                'measurements.category.activeMeasurements',
            ])
            ->orderByDesc('id')
            ->get();

        $orders->each->appendMeasurementsToItems();

        $slips = $orders
            ->flatMap(function (TailoringOrder $order): Collection {
                return $order->items
                    ->groupBy(fn ($item) => (string) ($item->tailoring_category_id ?? 'other'))
                    ->map(fn (Collection $items, string $categoryId) => $this->buildCuttingSlipForOrder($order, $categoryId, 'all'))
                    ->filter()
                    ->values();
            })
            ->values();

        if ($slips->isEmpty()) {
            return redirect()->back()->with('error', 'No cutting slips found for the selected orders.');
        }

        $this->markCuttingSlipsPrinted($slips);

        return view('print.tailoring.cutting-slips', [
            'slips' => $slips,
        ]);
    }

    protected function buildCuttingSlipForOrder(TailoringOrder $order, string $categoryId, string $modelId = 'all'): ?array
    {
        $items = $order->items
            ->filter(function ($item) use ($categoryId, $modelId): bool {
                $categoryMatches = (string) ($item->tailoring_category_id ?? 'other') === $categoryId;
                $modelMatches = $modelId === 'all' || (string) $item->tailoring_category_model_id === $modelId;

                return $categoryMatches && $modelMatches;
            })
            ->values();

        if ($items->isEmpty()) {
            return null;
        }

        return [
            'order' => $order,
            'items' => $items,
            'categoryId' => $categoryId,
            'modelId' => $modelId,
        ];
    }

    protected function markCuttingSlipsPrinted(Collection $slips): void
    {
        $orderIds = $slips->pluck('order.id')->filter()->unique()->values();

        if ($orderIds->isEmpty()) {
            return;
        }

        TailoringOrder::query()
            ->whereIn('id', $orderIds)
            ->update(['cutting_slip_printed_at' => now()]);
    }

    public function printOrderReceipt($id)
    {
        $action = new GetTailoringOrderAction();
        $result = $action->execute($id);

        if (! $result['success']) {
            return redirect()->back()->with('error', $result['message']);
        }

        $order = $result['data'];
        $companyName = Configuration::where('key', 'company_name')->value('value') ?? config('app.name');
        $companyAddress = Configuration::where('key', 'company_address')->value('value') ?? '';
        $companyPhone = Configuration::where('key', 'company_mobile')->value('value') ?? '';
        $companyEmail = Configuration::where('key', 'company_email')->value('value') ?? '';
        $gstNo = Configuration::where('key', 'gst_no')->value('value') ?? '';
        $enableLogoInPrint = Configuration::where('key', 'enable_logo_in_print')->value('value') ?? 'no';
        $companyLogo = cache('logo');

        $pdf = Pdf::loadView('print.tailoring.order-receipt-pdf', [
            'order' => $order,
            'companyName' => $companyName,
            'companyAddress' => $companyAddress,
            'companyPhone' => $companyPhone,
            'companyEmail' => $companyEmail,
            'gstNo' => $gstNo,
            'enableLogoInPrint' => $enableLogoInPrint,
            'companyLogo' => $companyLogo,
        ]);
        $pdf->setPaper('a4', 'portrait');
        $pdf->setOption('margin-top', 8);
        $pdf->setOption('margin-right', 8);
        $pdf->setOption('margin-bottom', 8);
        $pdf->setOption('margin-left', 8);

        $filename = 'order-receipt-'.str_replace('/', '-', $order->order_no).'.pdf';

        return $pdf->stream($filename);
    }

    public function printOrderReceiptThermal($id)
    {
        $action = new GetTailoringOrderAction();
        $result = $action->execute($id);

        if (! $result['success']) {
            return redirect()->back()->with('error', $result['message']);
        }

        $order = $result['data'];

        $categorySummary = $order->items->groupBy('tailoring_category_id')->map(function ($items) {
            $first = $items->first();

            return [
                'name' => $first->category->name ?? 'Other',
                'count' => $items->sum('quantity'),
                'amount' => $items->sum('total'),
            ];
        })->values();

        $thermal_printer_style = Configuration::where('key', 'thermal_printer_style')->value('value') ?? 'with_arabic';
        $thermal_printer_footer_english = Configuration::where('key', 'thermal_printer_footer_english')->value('value');
        $thermal_printer_footer_arabic = Configuration::where('key', 'thermal_printer_footer_arabic')->value('value');
        $enable_logo_in_print = Configuration::where('key', 'enable_logo_in_print')->value('value');
        $gst_no = Configuration::where('key', 'gst_no')->value('value');

        return view('print.tailoring.order-receipt-thermal', [
            'order' => $order,
            'categorySummary' => $categorySummary,
            'thermal_printer_style' => $thermal_printer_style,
            'thermal_printer_footer_english' => $thermal_printer_footer_english,
            'thermal_printer_footer_arabic' => $thermal_printer_footer_arabic,
            'enable_logo_in_print' => $enable_logo_in_print,
            'gst_no' => $gst_no,
        ]);
    }
}
