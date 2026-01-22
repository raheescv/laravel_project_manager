<?php

namespace App\Http\Controllers\Tailoring;

use App\Actions\Tailoring\Order\CreateTailoringOrderAction;
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
use App\Models\Country;
use App\Models\CustomerType;
use App\Models\Product;
use App\Models\Rack;
use App\Models\TailoringCategory;
use App\Models\TailoringCategoryModel;
use App\Models\TailoringMeasurementOption;
use App\Models\TailoringOrder;
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
        $query = TailoringOrder::with(['account:id,name', 'salesman:id,name']);

        // Apply filters
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_no', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_mobile', 'like', "%{$search}%")
                    ->orWhereHas('account', function ($subQ) use ($search) {
                        $subQ->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('customer_id') && $request->customer_id) {
            $query->where('account_id', $request->customer_id);
        }

        if ($request->has('from_date') && $request->from_date) {
            $dateField = $request->date_type === 'delivery_date' ? 'delivery_date' : 'order_date';
            $query->whereDate($dateField, '>=', $request->from_date);
        }

        if ($request->has('to_date') && $request->to_date) {
            $dateField = $request->date_type === 'delivery_date' ? 'delivery_date' : 'order_date';
            $query->whereDate($dateField, '<=', $request->to_date);
        }

        if ($request->has('payment_status') && $request->payment_status) {
            if ($request->payment_status === 'paid') {
                $query->where('balance', '<=', 0);
            } elseif ($request->payment_status === 'balance') {
                $query->where('balance', '>', 0);
            }
        }

        $query->orderBy('created_at', 'desc');

        $orders = $query->paginate($request->per_page ?? 20);

        if ($request->wantsJson()) {
            return response()->json($orders);
        }

        return view('tailoring.index', compact('orders'));
    }

    public function create()
    {
        return $this->page();
    }

    public function page($id = null)
    {
        $categories = TailoringCategory::with('activeModels')->active()->ordered()->get();
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
            'order_no' => TailoringOrder::generateOrderNo(),
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

        return Inertia::render('Tailoring/Order', [
            'order' => $orderData,
            'categories' => $categories,
            'measurementOptions' => $measurementOptions,
            'salesmen' => $salesmen,
            'customers' => $customers,
            'paymentMethods' => $paymentMethods,
            'customerTypes' => $customerTypes,
            'countries' => $countries,
        ]);
    }

    private function getMeasurementOptions(): array
    {
        $types = [
            'mar_model', 'cuff', 'cuff_cloth', 'cuff_model',
            'collar', 'collar_cloth', 'collar_model', 'fp_model',
            'pen', 'side_pt_model', 'stitching', 'button',
        ];

        $options = [];
        foreach ($types as $type) {
            $options[$type] = TailoringMeasurementOption::getOptionsByType($type);
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
        $action = new GetTailoringOrderAction();
        $result = $action->execute($id);

        if (! $result['success']) {
            return redirect()->route('tailoring::order::index')->with('error', $result['message']);
        }

        return Inertia::render('Tailoring/Order/Show', [
            'order' => $result['data'],
        ]);
    }

    public function store(Request $request)
    {
        $action = new CreateTailoringOrderAction();
        $result = $action->execute($request->all(), Auth::id());

        if ($result['success']) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'redirect_url' => route('tailoring::order::show', $result['data']->id)
                ]);
            }
            return redirect()->route('tailoring::order::show', $result['data']->id)
                ->with('success', $result['message']);
        }

        if ($request->wantsJson()) {
            return response()->json(['message' => $result['message']], 422);
        }

        return back()->withErrors(['error' => $result['message']]);
    }

    public function update(Request $request, $id)
    {
        $action = new UpdateTailoringOrderAction();
        $result = $action->execute($id, $request->all(), Auth::id());

        if ($result['success']) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'redirect_url' => route('tailoring::order::show', $id)
                ]);
            }
            return redirect()->route('tailoring::order::show', $id)
                ->with('success', $result['message']);
        }

        if ($request->wantsJson()) {
            return response()->json(['message' => $result['message']], 422);
        }

        return back()->withErrors(['error' => $result['message']]);
    }

    public function destroy($id)
    {
        $order = TailoringOrder::findOrFail($id);
        $order->deleted_by = Auth::id();
        $order->save();
        $order->delete();

        return redirect()->route('tailoring::order::index')
            ->with('success', 'Order deleted successfully');
    }

    // API Routes
    public function getCategories(): JsonResponse
    {
        $categories = TailoringCategory::with('activeModels')->active()->ordered()->get();

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

    public function addMeasurementOption(Request $request): JsonResponse
    {
        $request->validate([
            'option_type' => 'required|in:mar_model,cuff,cuff_cloth,cuff_model,collar,collar_cloth,collar_model,fp_model,pen,side_pt_model,stitching,button',
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
        $unitPrice = (float) ($request->unit_price ?? 0);
        $stitchRate = (float) ($request->stitch_rate ?? 0);
        $discount = (float) ($request->discount ?? 0);
        $tax = (float) ($request->tax ?? 0);

        $grossAmount = $quantity * $unitPrice;
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

        $orders = $query->latest()
            ->limit(20)
            ->get(['id', 'order_no', 'customer_name', 'customer_mobile', 'order_date', 'delivery_date', 'status', 'completion_status']);

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
        $item = \App\Models\TailoringOrderItem::findOrFail($itemId);
        $item->updateCompletion($request->all());

        // Reload with relationships and append measurements
        $item = $item->fresh(['category', 'categoryModel', 'product', 'unit', 'tailor']);
        $order = $item->order()->with('measurements')->first();

        // Mock items collection for appendMeasurementsToItems helper
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

    public function printCuttingSlip($id, $categoryId, $modelId)
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
