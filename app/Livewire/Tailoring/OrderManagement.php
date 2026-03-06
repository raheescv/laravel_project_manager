<?php

namespace App\Livewire\Tailoring;

use App\Models\TailoringOrder;
use App\Models\TailoringOrderItem;
use Livewire\Component;
use Livewire\WithPagination;

class OrderManagement extends Component
{
    use WithPagination;

    public $search = '';

    public $customer_id = '';

    public $pending_only = 1;

    public $limit = 10;

    public $sortField = 'tailoring_orders.order_date';

    public $sortDirection = 'desc';

    public $selectedOrderDetails = [];

    public $selectedOrderItems = [];

    public $expandedOrderIds = [];

    public $orderItemsByOrder = [];

    protected $paginationTheme = 'bootstrap';

    protected $listeners = [
        'Tailoring-Receipts-Refresh' => '$refresh',
    ];

    protected $queryString = [
        'search' => ['except' => ''],
        'customer_id' => ['except' => ''],
        'pending_only' => ['except' => 1],
    ];

    public function updated($key, $value)
    {
        if (! in_array($key, ['search', 'customer_id', 'pending_only', 'limit'])) {
            return;
        }
        $this->resetExpandedRows();
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'desc';
        }
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->customer_id = '';
        $this->pending_only = 1;
        $this->resetExpandedRows();
        $this->resetPage();
        $this->dispatch('order-management-clear-customer');
    }

    public function viewCustomerOrders($accountId = null, $customerName = '', $customerMobile = '')
    {
        if (! empty($accountId)) {
            $this->customer_id = (string) $accountId;
            $this->search = '';
        } else {
            $this->customer_id = '';
            $this->search = trim((string) ($customerName ?: $customerMobile));
        }
        $this->resetExpandedRows();
        $this->resetPage();
    }

    protected function resetExpandedRows(): void
    {
        $this->expandedOrderIds = [];
        $this->orderItemsByOrder = [];
    }

    public function toggleOrderItems($orderId): void
    {
        $orderId = (int) $orderId;
        $isExpanded = in_array($orderId, $this->expandedOrderIds, true);

        if ($isExpanded) {
            $this->expandedOrderIds = array_values(array_filter(
                $this->expandedOrderIds,
                fn ($id) => (int) $id !== $orderId
            ));

            return;
        }

        $this->expandedOrderIds[] = $orderId;

        if (array_key_exists($orderId, $this->orderItemsByOrder)) {
            return;
        }

        $this->orderItemsByOrder[$orderId] = TailoringOrderItem::query()
            ->select([
                'id',
                'tailoring_order_id',
                'item_no',
                'product_name',
                'quantity',
                'completed_quantity',
                'pending_quantity',
                'delivered_quantity',
                'completion_status',
                'delivery_status',
            ])
            ->where('tailoring_order_id', $orderId)
            ->orderBy('item_no')
            ->get()
            ->map(fn ($item) => [
                'id' => (int) $item->id,
                'item_no' => (int) $item->item_no,
                'product_name' => (string) ($item->product_name ?? ''),
                'quantity' => (float) $item->quantity,
                'completed_quantity' => (float) $item->completed_quantity,
                'pending_quantity' => (float) $item->pending_quantity,
                'delivered_quantity' => (float) $item->delivered_quantity,
                'completion_status' => (string) ($item->completion_status ?? ''),
                'delivery_status' => (string) ($item->delivery_status ?? ''),
            ])
            ->values()
            ->toArray();
    }

    public function openReceiptModal($accountId = null, $customerName = '', $customerMobile = '', $displayName = '')
    {
        $this->dispatch('Open-TailoringCustomerReceipt', [
            'account_id' => $accountId ?: null,
            'customer_name' => $customerName ?? '',
            'customer_mobile' => $customerMobile ?? '',
            'display_name' => $displayName ?? '',
        ]);
    }

    public function openItemsModal($orderId)
    {
        $order = TailoringOrder::with([
            'items:id,tailoring_order_id,item_no,inventory_id,product_id,product_name,product_color,quantity,quantity_per_item,unit_price,stitch_rate,discount,tax,total,tailoring_notes,tailoring_category_id,tailoring_category_model_id,tailoring_category_model_type_id,unit_id',
            'items.category:id,name',
            'items.category.activeMeasurements:id,tailoring_category_id,field_key',
            'items.categoryModel:id,name',
            'items.categoryModelType:id,name',
            'items.unit:id,name',
        ])->find($orderId);

        if (! $order) {
            $this->dispatch('error', ['message' => 'Order not found']);

            return;
        }

        $order->appendMeasurementsToItems();

        $this->selectedOrderDetails = [
            'id' => $order->id,
            'order_no' => $order->order_no,
            'order_date' => $order->order_date ? $order->order_date->format('Y-m-d') : null,
            'account_id' => $order->account_id,
            'customer_name' => $order->account?->name ?? $order->customer_name,
            'customer_mobile' => $order->customer_mobile,
        ];

        $this->selectedOrderItems = $order->items
            ->sortBy('item_no')
            ->map(function ($item) {
                $measurementData = [];
                $activeMeasurementKeys = $item->category?->activeMeasurements?->pluck('field_key')->toArray() ?? [];
                foreach ($activeMeasurementKeys as $fieldKey) {
                    if (isset($item->{$fieldKey}) && $item->{$fieldKey} !== null && $item->{$fieldKey} !== '') {
                        $measurementData[$fieldKey] = $item->{$fieldKey};
                    }
                }

                $prefill = [
                    'inventory_id' => $item->inventory_id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'product_color' => $item->product_color,
                    'quantity' => (float) $item->quantity,
                    'quantity_per_item' => (float) ($item->quantity_per_item ?: 1),
                    'unit_price' => (float) $item->unit_price,
                    'stitch_rate' => (float) ($item->stitch_rate ?: 0),
                    'discount' => (float) ($item->discount ?: 0),
                    'tax' => (float) ($item->tax ?: 0),
                    'total' => (float) $item->total,
                    'tailoring_notes' => $item->tailoring_notes,
                    'tailoring_category_id' => (int) $item->tailoring_category_id,
                    'tailoring_category_model_id' => $item->tailoring_category_model_id ? (int) $item->tailoring_category_model_id : null,
                    'tailoring_category_model_name' => $item->categoryModel?->name,
                    'tailoring_category_model_type_id' => $item->tailoring_category_model_type_id ? (int) $item->tailoring_category_model_type_id : null,
                    'tailoring_category_model_type_name' => $item->categoryModelType?->name,
                ];
                $prefill = array_merge($prefill, $measurementData);

                return [
                    'id' => (int) $item->id,
                    'item_no' => $item->item_no,
                    'product_name' => $item->product_name,
                    'category' => $item->category?->name,
                    'model' => $item->categoryModel?->name,
                    'model_type' => $item->categoryModelType?->name,
                    'color' => $item->product_color,
                    'quantity' => (float) $item->quantity,
                    'unit' => $item->unit?->name,
                    'unit_price' => (float) $item->unit_price,
                    'total' => (float) $item->total,
                    'notes' => $item->tailoring_notes,
                    'prefill' => $prefill,
                ];
            })
            ->values()
            ->toArray();

        $this->dispatch('toggle-order-items-modal');
    }

    public function openTailorActionModal($orderId): void
    {
        $this->dispatch('open-tailor-action-modal', orderId: (int) $orderId);
    }

    protected function getBaseQuery()
    {
        return TailoringOrder::query()
            ->with([
                'account:id,name,mobile',
                'salesman:id,name',
            ])
            ->when(trim((string) $this->customer_id) !== '', fn ($q) => $q->where('tailoring_orders.account_id', $this->customer_id))
            ->when(trim((string) $this->search) !== '', function ($q) {
                $value = '%'.trim((string) $this->search).'%';

                return $q->where(function ($sub) use ($value) {
                    $sub->where('tailoring_orders.order_no', 'like', $value)
                        ->orWhere('tailoring_orders.customer_name', 'like', $value)
                        ->orWhere('tailoring_orders.customer_mobile', 'like', $value)
                        ->orWhereHas('account', function ($acc) use ($value) {
                            $acc->where('accounts.name', 'like', $value)
                                ->orWhere('accounts.mobile', 'like', $value);
                        });
                });
            })
            ->when((int) $this->pending_only === 1, fn ($q) => $q->where('tailoring_orders.status', 'pending'))
            ->when((int) $this->pending_only === 2, fn ($q) => $q->where('tailoring_orders.balance', '>', 0));
    }

    protected function getCustomerSummary($query)
    {
        return (clone $query)
            ->leftJoin('accounts', 'accounts.id', '=', 'tailoring_orders.account_id')
            ->selectRaw('
                tailoring_orders.account_id,
                COALESCE(accounts.name, tailoring_orders.customer_name) as customer_display,
                COALESCE(NULLIF(accounts.mobile, ""), tailoring_orders.customer_mobile) as customer_mobile,
                tailoring_orders.customer_name,
                tailoring_orders.customer_mobile as order_customer_mobile,
                COUNT(tailoring_orders.id) as order_count,
                SUM(tailoring_orders.grand_total) as grand_total,
                SUM(tailoring_orders.paid) as paid,
                SUM(tailoring_orders.balance) as balance,
                MAX(tailoring_orders.order_date) as latest_order_date
            ')
            ->groupBy(
                'tailoring_orders.account_id',
                'accounts.name',
                'accounts.mobile',
                'tailoring_orders.customer_name',
                'tailoring_orders.customer_mobile'
            )
            ->orderByDesc('latest_order_date')
            ->limit(30)
            ->get();
    }

    public function render()
    {
        $query = $this->getBaseQuery();
        $totals = clone $query;
        $customerSummary = $this->getCustomerSummary($query);

        $sql = '
            SUM(grand_total) as grand_total,
            SUM(paid) as paid,
            SUM(balance) as balance
        ';
        $total = $totals->selectRaw($sql)->first();

        $total = [
            'grand_total' => $total->grand_total ?? 0,
            'paid' => $total->paid ?? 0,
            'balance' => $total->balance ?? 0,
        ];

        return view('livewire.tailoring.order-management', [
            'total' => $total,
            'customerSummary' => $customerSummary,
            'data' => $query->orderBy($this->sortField, $this->sortDirection)
                ->paginate($this->limit),
        ]);
    }
}
