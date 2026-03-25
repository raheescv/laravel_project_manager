<?php

namespace App\Livewire\LocalPurchaseOrder;

use App\Actions\LocalPurchaseOrder\CreateAction;
use App\Models\Account;
use App\Models\Product;
use App\Models\PurchaseRequest;
use Livewire\Component;

class Page extends Component
{
    public ?int $vendor_id = null;

    public array $selectedRequests = [];

    public array $items = [];

    public $vendors = [];

    public $productOptions = [];

    public $approvedPurchaseRequests = [];

    public function mount()
    {
        $this->vendors = Account::select('id', 'name')->vendor()->latest()->get();

        $this->productOptions = Product::select('id', 'name')->orderBy('name')->get();

        $this->approvedPurchaseRequests = PurchaseRequest::approved()
            ->with('branch')
            ->latest()
            ->get()
            ->map(fn ($pr) => [
                'id' => $pr->id,
                'label' => "PR-{$pr->id} ({$pr->branch->name})",
            ]);
    }

    public function getApprovedPurchaseRequestsWithProducts()
    {
        return PurchaseRequest::with('products.product')
            ->where('status', 'approved')
            ->get()
            ->map(function ($pr) {
                return [
                    'id' => $pr->id,
                    'products' => $pr->products->map(function ($p) {
                        return [
                            'product_id' => $p->product_id,
                            'name' => $p->product->name,
                            'quantity' => $p->quantity,
                        ];
                    }),
                ];
            });
    }

    public function save()
    {
        $response = (new CreateAction())->execute([
            'vendor_id' => $this->vendor_id,
            'items' => $this->items,
        ]);

        if ($response['success']) {
            $this->dispatch('success', ['message' => $response['message']]);

            $this->redirectRoute('lpo::index');
        } else {
            $this->dispatch('error', ['message' => $response['message']]);
        }
    }

    public function render()
    {
        return view('livewire.local-purchase-order.page');
    }
}
