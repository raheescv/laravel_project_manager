<?php

namespace App\Livewire\LpoPurchase;

use App\Actions\LpoPurchase\CreateUpdateAction;
use App\Models\LocalPurchaseOrder;
use App\Models\Purchase;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Page extends Component
{
    public ?int $purchase_id = null;

    public ?int $local_purchase_order_id = null;

    public ?string $invoice_no = null;

    public ?string $date = null;

    public ?string $remarks = null;

    public float $other_discount = 0;

    public float $freight = 0;

    public array $items = [];

    public $approvedLpos = [];

    public ?string $vendor_name = null;

    public function mount(?int $purchase_id = null)
    {
        $this->approvedLpos = LocalPurchaseOrder::approved()
            ->with('branch', 'vendor')
            ->get()
            ->mapWithKeys(fn ($lpo) => [
                $lpo->id => "LPO #{$lpo->id} - {$lpo->vendor?->name} ({$lpo->branch?->name})",
            ]);

        $this->date = date('Y-m-d');

        if ($purchase_id) {
            $purchase = Purchase::with(['items.product', 'items.unit', 'localPurchaseOrder.vendor', 'localPurchaseOrder.items'])->findOrFail($purchase_id);
            $this->purchase_id = $purchase->id;
            $this->local_purchase_order_id = $purchase->local_purchase_order_id;
            $this->vendor_name = $purchase->localPurchaseOrder?->vendor?->name;
            $this->invoice_no = $purchase->invoice_no;
            $this->date = $purchase->date;
            $this->remarks = $purchase->address;
            $this->other_discount = (float) $purchase->other_discount;
            $this->freight = (float) $purchase->freight;
            $this->items = $purchase->items->map(fn ($item) => [
                'id' => $item->id,
                'local_purchase_order_item_id' => null,
                'product_id' => $item->product_id,
                'product_name' => $item->product?->name,
                'unit_id' => $item->unit_id,
                'unit_name' => $item->unit?->name,
                'ordered_quantity' => $this->getOrderedQuantity($item->product_id),
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'discount' => (float) $item->discount,
                'tax' => (float) $item->tax,
            ])->toArray();
        }
    }

    private function getOrderedQuantity(int $productId): float
    {
        if (! $this->local_purchase_order_id) {
            return 0;
        }

        $lpo = LocalPurchaseOrder::with('items')->find($this->local_purchase_order_id);
        $item = $lpo?->items->firstWhere('product_id', $productId);

        return (float) ($item?->quantity ?? 0);
    }

    public function getLpoItems()
    {
        if (! $this->local_purchase_order_id) {
            $this->vendor_name = null;

            return [];
        }

        $lpo = LocalPurchaseOrder::with(['items.product.unit', 'vendor'])
            ->find($this->local_purchase_order_id);

        if (! $lpo) {
            $this->vendor_name = null;

            return [];
        }

        $this->vendor_name = $lpo->vendor?->name;

        return $lpo->items
            ->map(fn ($item) => [
                'local_purchase_order_item_id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product->name,
                'unit_id' => $item->product->unit_id ?? null,
                'unit_name' => $item->product->unit?->name ?? '-',
                'ordered_quantity' => (float) $item->quantity,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->rate,
                'discount' => 0,
                'tax' => $item->product->tax,
            ])
            ->toArray();
    }

    public function save()
    {
        try {
            DB::beginTransaction();
            $data = [
                'local_purchase_order_id' => $this->local_purchase_order_id,
                'invoice_no' => $this->invoice_no,
                'date' => $this->date,
                'remarks' => $this->remarks,
                'other_discount' => $this->other_discount,
                'freight' => $this->freight,
                'items' => $this->items,
            ];
            $response = (new CreateUpdateAction())->execute($data, Auth::id(), $this->purchase_id);
            if (! $response['success']) {
                throw new Exception($response['message'], 1);
            }
            DB::commit();
            $this->dispatch('success', ['message' => $response['message']]);
            $this->redirectRoute('lpo-purchase::index');
        } catch (Exception $e) {
            DB::rollback();
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.lpo-purchase.page');
    }
}
