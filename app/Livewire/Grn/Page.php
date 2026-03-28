<?php

namespace App\Livewire\Grn;

use App\Actions\Grn\CreateUpdateAction;
use App\Models\Grn;
use App\Models\LocalPurchaseOrder;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Page extends Component
{
    public ?int $grn_id = null;

    public ?int $local_purchase_order_id = null;

    public ?string $date = null;

    public ?string $remarks = null;

    public array $items = [];

    public $approvedLpos = [];

    public ?string $vendor_name = null;

    public function mount(?int $grn_id = null)
    {
        $this->approvedLpos = LocalPurchaseOrder::approved()
            ->with('branch', 'vendor')
            ->get()
            ->mapWithKeys(fn ($lpo) => [
                $lpo->id => "LPO #{$lpo->id} - {$lpo->vendor?->name} ({$lpo->branch?->name})",
            ]);

        $this->date = date('Y-m-d');

        if ($grn_id) {
            $grn = Grn::with(['items.product', 'items.localPurchaseOrderItem', 'localPurchaseOrder.vendor'])->findOrFail($grn_id);
            $this->grn_id = $grn->id;
            $this->local_purchase_order_id = $grn->local_purchase_order_id;
            $this->vendor_name = $grn->localPurchaseOrder?->vendor?->name;
            $this->date = $grn->date;
            $this->remarks = $grn->remarks;
            $this->items = $grn->items->map(fn ($item) => [
                'id' => $item->id,
                'local_purchase_order_item_id' => $item->local_purchase_order_item_id,
                'product_id' => $item->product_id,
                'product_name' => $item->product?->name,
                'ordered_quantity' => $item->localPurchaseOrderItem?->quantity,
                'rate' => $item->localPurchaseOrderItem?->rate,
                'quantity' => $item->quantity,
            ])->toArray();
        }
    }

    public function getLpoItems()
    {
        if (! $this->local_purchase_order_id) {
            $this->vendor_name = null;

            return [];
        }

        $lpo = LocalPurchaseOrder::with(['items.product', 'vendor'])
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
                'ordered_quantity' => $item->quantity,
                'rate' => $item->rate,
                'quantity' => $item->quantity,
            ])
            ->toArray();
    }

    public function save()
    {
        try {
            DB::beginTransaction();
            $data = [
                'local_purchase_order_id' => $this->local_purchase_order_id,
                'date' => $this->date,
                'remarks' => $this->remarks,
                'items' => $this->items,
            ];
            $response = (new CreateUpdateAction())->execute($data, Auth::id(), $this->grn_id);
            if (! $response['success']) {
                throw new Exception($response['message'], 1);
            }
            DB::commit();
            $this->dispatch('success', ['message' => $response['message']]);
            $this->redirectRoute('grn::index');
        } catch (Exception $e) {
            DB::rollback();
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.grn.page');
    }
}
