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
use Inertia\Response;

class OrderController extends Controller
{
    // Web Routes
    public function index()
    {
        $orders = TailoringOrder::with(['account:id,name', 'salesman:id,name'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return Inertia::render('Tailoring/Order/Index', [
            'orders' => $orders,
        ]);
    }

    public function create()
    {
        return $this->page();
    }

    public function page($id = null)
    {
        $categories = TailoringCategory::with('activeModels')->active()->ordered()->get();
        $measurementOptions = $this->getMeasurementOptions();
        $salesmen = User::where('type', 'employee')->orWhere('type', 'user')->pluck('name', 'id')->toArray();
        
        // Get default customer if configured
        $useDefaultCustomer = true; // Can be from config
        $customers = [];
        if ($useDefaultCustomer) {
            $customers[3] = [
                'id' => 3,
                'name' => 'General Customer',
                'mobile' => '',
            ];
        }

        $orderData = [
            'id' => null,
            'order_no' => TailoringOrder::generateOrderNo(),
            'order_date' => date('Y-m-d'),
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

        return Inertia::render('Tailoring/Order', [
            'order' => $orderData,
            'categories' => $categories,
            'measurementOptions' => $measurementOptions,
            'salesmen' => $salesmen,
            'customers' => $customers,
        ]);
    }

    private function getMeasurementOptions(): array
    {
        $types = [
            'mar_model', 'cuff', 'cuff_cloth', 'cuff_model',
            'collar', 'collar_cloth', 'collar_model', 'fp_model',
            'pen', 'side_pt_model', 'stitching', 'button'
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

        return Inertia::render('Tailoring/JobCompletion', [
            'racks' => $racks,
            'tailors' => $tailors,
            'cutters' => $cutters,
        ]);
    }

    public function show($id)
    {
        $action = new GetTailoringOrderAction();
        $result = $action->execute($id);
        
        if (!$result['success']) {
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
            return redirect()->route('tailoring::order::show', $result['data']->id)
                ->with('success', $result['message']);
        }

        return back()->withErrors(['error' => $result['message']]);
    }

    public function update(Request $request, $id)
    {
        $action = new UpdateTailoringOrderAction();
        $result = $action->execute($id, $request->all(), Auth::id());

        if ($result['success']) {
            return redirect()->route('tailoring::order::show', $id)
                ->with('success', $result['message']);
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
        $total = $netAmount + $taxAmount + $stitchRate;

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

        $orders = $query->limit(20)->get(['id', 'order_no', 'customer_name', 'customer_mobile', 'order_date', 'status']);

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

        return response()->json([
            'success' => true,
            'message' => 'Item completion updated successfully',
            'data' => $item->fresh(),
        ]);
    }
}
