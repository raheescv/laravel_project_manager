<?php

namespace App\Livewire\PurchaseRequest;

use App\Actions\PurchaseRequest\CreateUpdateAction;
use App\Models\Product;
use App\Models\PurchaseRequest;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Page extends Component
{
    public PurchaseRequest $purchase_request;

    public array $products = [];

    public function mount(?int $purchase_request_id = null)
    {
        if ($purchase_request_id) {
            $this->purchase_request = PurchaseRequest::with('products')->findOrFail($purchase_request_id);
            $this->products = $this->purchase_request->products->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                ];
            })->toArray();
        }
    }

    #[Computed()]
    public function productOptions()
    {
        return Product::orderBy('name')->get(['name', 'id']);
    }

    public function save()
    {
        try {
            DB::beginTransaction();
            $response = (new CreateUpdateAction())->execute($this->products, Auth::id(), $this->purchase_request->id ?? null);
            if (! $response['success']) {
                throw new Exception($response['message'], 1);
            }
            $this->purchase_request = $response['data'];
            $this->dispatch('success', ['message' => $response['message']]);

            $this->redirectRoute('purchase-request::index');
            DB::commit();

        } catch (Exception $e) {
            DB::rollback();
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.purchase-request.page');
    }
}
