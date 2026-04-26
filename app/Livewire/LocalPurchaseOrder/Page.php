<?php

namespace App\Livewire\LocalPurchaseOrder;

use App\Actions\LocalPurchaseOrder\CreateUpdateAction;
use App\Models\Account;
use App\Models\LocalPurchaseOrder;
use App\Models\Product;
use App\Models\PurchaseRequest;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Page extends Component
{
    public ?int $order_id = null;

    public ?int $vendor_id = null;

    public ?string $date = null;

    public array $selectedRequests = [];

    public array $items = [];

    public $vendors = [];

    public $productOptions = [];

    public $accountOptions = [];

    public $approvedPurchaseRequests = [];

    public function mount(?int $order_id = null)
    {
        $this->vendors = Account::select('id', 'name')->vendor()->latest()->get();

        $this->productOptions = Product::select('id', 'name', 'cost', 'expense_account_id')->orderBy('name')->get();

        $this->date = date('Y-m-d');

        $idsForAccountLabels = collect($this->productOptions)
            ->pluck('expense_account_id')
            ->filter()
            ->unique()
            ->values();

        if ($order_id) {
            $order = LocalPurchaseOrder::with('items')->findOrFail($order_id);
            $this->order_id = $order->id;
            $this->vendor_id = $order->vendor_id;
            $this->date = $order->date;
            $this->items = $order->items->map(fn ($item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'rate' => $item->rate,
                'account_id' => $item->account_id,
            ])->toArray();
            $idsForAccountLabels = $idsForAccountLabels->merge(
                collect($this->items)->pluck('account_id')->filter()
            )->unique()->values();
        }

        $this->accountOptions = Account::query()
            ->select('id', 'name')
            ->where(function ($q) use ($idsForAccountLabels): void {
                $q->where('account_type', 'expense');
                if ($idsForAccountLabels->isNotEmpty()) {
                    $q->orWhereIn('id', $idsForAccountLabels);
                }
            })
            ->orderBy('name')
            ->get();

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
            ->approved()
            ->latest()
            ->get()
            ->map(function ($pr) {
                return [
                    'id' => $pr->id,
                    'products' => $pr->products->map(function ($p) {
                        return [
                            'product_id' => $p->product_id,
                            'name' => $p->product->name,
                            'quantity' => $p->quantity,
                            'rate' => $p->product->cost,
                        ];
                    }),
                ];
            });
    }

    public function save()
    {
        try {
            DB::beginTransaction();
            $data = [
                'vendor_id' => $this->vendor_id,
                'date' => $this->date,
                'items' => $this->items,
            ];
            $response = (new CreateUpdateAction())->execute($data, Auth::id(), $this->order_id);
            if (! $response['success']) {
                throw new Exception($response['message'], 1);
            }
            DB::commit();
            $this->dispatch('success', ['message' => $response['message']]);
            $this->redirectRoute('lpo::index');
        } catch (Exception $e) {
            DB::rollback();
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.local-purchase-order.page');
    }
}
