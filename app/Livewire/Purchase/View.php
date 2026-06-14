<?php

namespace App\Livewire\Purchase;

use App\Actions\Purchase\UpdateAction;
use App\Models\Purchase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class View extends Component
{
    public $table_id;

    public $items = [];

    public $payments = [];

    public Purchase $purchase;

    public $purchases = [];

    public function mount($table_id = null): void
    {
        $this->table_id = $table_id;

        if (! $this->table_id) {
            $this->redirect(route('purchase::index'), true);

            return;
        }

        $this->purchase = Purchase::with([
            'account:id,name,mobile,email',
            'branch:id,name',
            'localPurchaseOrder:id',
            'items.product:id,name,barcode,unit_id,cost',
            'items.product.audits.user:id,name',
            'items.unit:id,name',
            'items.account:id,name',
            'items.audits.user:id,name',
            'createdUser:id,name',
            'updatedUser:id,name',
            'cancelledUser:id,name',
            'payments.paymentMethod:id,name',
            'payments.audits.user:id,name',
            'journals.entries.account:id,name',
            'audits.user:id,name',
        ])->find($this->table_id);

        if (! $this->purchase) {
            $this->redirect(route('purchase::index'), true);

            return;
        }

        $this->purchases = $this->purchase->toArray();
        $this->items = $this->purchase->items->mapWithKeys(function ($item) {
            $key = 'purchase-item-'.$item->product_id.'-'.$item->id;

            return [
                $key => [
                    'id' => $item->id,
                    'key' => $key,
                    'product_id' => $item->product_id,
                    'account_id' => $item->account_id,
                    'name' => $item->name,
                    'barcode' => $item->product?->barcode,
                    'unit' => $item->unit?->name,
                    'unit_id' => $item->unit_id,
                    'conversion_factor' => $item->conversion_factor,
                    'tax_amount' => $item->tax_amount,
                    'unit_price' => $item->unit_price,
                    'quantity' => round((float) $item->quantity, 3),
                    'gross_amount' => $item->gross_amount,
                    'discount' => $item->discount,
                    'tax' => $item->tax,
                    'total' => $item->total,
                    'created_by' => $item->created_by,
                ],
            ];
        })->toArray();

        $this->payments = $this->purchase->payments->map->only(['id', 'amount', 'date', 'payment_method_id', 'created_by', 'name'])->toArray();
    }

    public function save(string $type = 'completed'): void
    {
        abort_unless(auth()->user()?->can('purchase.edit'), 403);
        $oldStatus = $this->purchases['status'] ?? $this->purchase->status;

        try {
            DB::beginTransaction();

            if (! count($this->items)) {
                throw new \Exception('Please add any item', 1);
            }

            $this->purchases['status'] = $type;
            $this->purchases['items'] = $this->items;
            $this->purchases['payments'] = $this->payments;

            $response = (new UpdateAction())->execute($this->purchases, $this->table_id, Auth::id());

            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }

            $this->mount($this->table_id);

            DB::commit();
            $this->dispatch('success', ['message' => $response['message']]);
        } catch (\Throwable $th) {
            DB::rollBack();
            $this->purchases['status'] = $oldStatus;
            $this->dispatch('error', ['message' => $th->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.purchase.view');
    }
}
