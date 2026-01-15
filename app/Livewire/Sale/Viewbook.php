<?php

namespace App\Livewire\Sale;

use App\Actions\Sale\UpdateAction;
use App\Models\Sale;
use App\Models\SaleReturnItem;
use App\Models\CustomerMeasurement;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class Viewbook extends Component
{
    public $table_id;

    public $items = [];

    public $sale_return_items = [];

    public $payments = [];

    public $sale;

    public $sales = [];

    public $status;

    public $customer_measurements = []; // ✅ New property

    public function mount($table_id = null)
    {
        $this->table_id = $table_id;

        if (! $this->table_id) {
            return;
        }

        // Load sale with relations
        $this->sale = Sale::with(
                'account:id,name,mobile',
                'branch:id,name',
                'items.product:id,name',
                'items.employee:id,name',
                'createdUser:id,name',
                'updatedUser:id,name',
                'cancelledUser:id,name',
                'payments.paymentMethod:id,name'
            )
            ->where('type', 'booking')
            ->find($this->table_id);

        if (! $this->sale) {
            return redirect()->route('sale::index');
        }

        // Default selected value
        $this->status = $this->sale->status;
        $this->sales = $this->sale->toArray();

        $item_ids = [];

        // Map sale items with category/subcategory names
        $categoryIds = $this->sale->items->pluck('category_id')->unique()->filter()->values()->all();
        $subCategoryIds = $this->sale->items->pluck('subcategory_id')->unique()->filter()->values()->all();
        $categoryNames = $categoryIds ? \App\Models\MeasurementCategory::whereIn('id', $categoryIds)->pluck('name', 'id')->toArray() : [];
        $subCategoryNames = $subCategoryIds ? \App\Models\MeasurementSubCategory::whereIn('id', $subCategoryIds)->pluck('name', 'id')->toArray() : [];

        $this->items = $this->sale->items->mapWithKeys(function ($item) use (&$item_ids, $categoryNames, $subCategoryNames) {
            $key = $item['employee_id'] . '-' . $item['inventory_id'];
            $item_ids[] = $item['id'];
            $subCatName = $subCategoryNames[$item['subcategory_id']] ?? null;
            if (empty($subCatName) && !empty($item['subcategory_id'])) {
                $subCatName = 'ID: ' . $item['subcategory_id'];
            }
            if (empty($subCatName)) {
                $subCatName = '-';
            }
            return [
                $key => [
                    'id' => $item['id'],
                    'key' => $key,
                    'employee_id' => $item['employee_id'],
                    'inventory_id' => $item['inventory_id'],
                    'product_id' => $item['product_id'],
                    'sale_combo_offer_id' => $item['sale_combo_offer_id'],
                    'name' => $item['name'],
                    'employee_name' => $item['employee_name'],
                    'assistant_name' => $item['assistant_name'],
                    'tax_amount' => $item['tax_amount'],
                    'unit_price' => $item['unit_price'],
                    'quantity' => round($item['quantity'], 3),
                    'gross_amount' => $item['gross_amount'],
                    'discount' => $item['discount'],
                    'tax' => $item['tax'],
                    'total' => $item['total'],
                    'effective_total' => $item['effective_total'],
                    'created_by' => $item['created_by'],
                    'category_id' => $item['category_id'] ?? null,
                    'category_name' => $categoryNames[$item['category_id']] ?? '-',
                    'sub_category_id' => $item['sub_category_id'] ?? null,
                    'sub_category_name' => $subCatName,
                ],
            ];
        })->toArray();

        // Sale return items
        $this->sale_return_items = SaleReturnItem::with(
                'saleReturn:id,other_discount,total',
                'product:id,name'
            )
            ->whereIn('sale_item_id', $item_ids)
            ->get();

        // Payments
        $this->payments = $this->sale->payments
            ->map
            ->only(['id', 'amount', 'date', 'payment_method_id', 'created_by', 'name'])
            ->toArray();

        // Customer measurements
        // Customer measurements
        $this->customer_measurements = CustomerMeasurement::with(
            'category',   // your category() relation
            'template'    // use the correct method name
        )
        ->where('sale_id', $this->table_id)
        ->get()
        ->map(function ($cm) {
            return [
                'id' => $cm->id,
                'customer_id' => $cm->customer_id,
                'category_id' => $cm->category_id,
                'sub_category_id' => $cm->sub_category_id,
                'measurement_template_id' => $cm->measurement_template_id,
                'value' => $cm->value,
                'size' => $cm->size,
                'width' => $cm->width,
                'quantity' => $cm->quantity,
                'category_name' => $cm->category->name ?? null,
                'template_name' => $cm->template->name ?? null, // updated here
            ];
        })->toArray();

            }

    public function updatedStatus($value)
    {
        Log::info('Status changed', ['value' => $value]);

        $this->sale->status = $value;
        $this->sale->updated_by = Auth::id();
        $this->sale->save();

        // Keep both in sync
        $this->status = $value;
        $this->sales['status'] = $value;

        $this->dispatch('success', [
            'message' => 'Status updated successfully'
        ]);
    }

    public function save($type = 'completed')
    {
        try {
            $oldStatus = $this->sales['status'];
            DB::beginTransaction();

            if (! count($this->items)) {
                throw new \Exception('Please add any item', 1);
            }

            $this->sales['status'] = $type;
            $this->sales['items'] = $this->items;
            $this->sales['payments'] = $this->payments;

            $user_id = Auth::id();
            $response = (new UpdateAction())->execute($this->sales, $this->table_id, $user_id);

            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }

            $this->mount($this->table_id);

            DB::commit();
            $this->dispatch('success', ['message' => $response['message']]);
        } catch (\Throwable $th) {
            DB::rollback();
            $this->dispatch('error', ['message' => $th->getMessage()]);
            $this->sales['status'] = $oldStatus;
        }
    }

    public function sendToWhatsapp()
    {
        $response = Sale::sendToWhatsapp($this->table_id);

        if (! $response['success']) {
            $this->dispatch('error', ['message' => $response['message']]);
        } else {
            $this->dispatch('success', ['message' => $response['message']]);
        }
    }

    public function openChangeSessionModal(): void
    {
        $this->dispatch('ToggleChangeSessionModal');
    }

    public function render()
    {
        return view('livewire.sale.viewbook');
    }
}
