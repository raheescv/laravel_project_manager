<?php

namespace App\Livewire\InventoryTransfer;

use App\Actions\InventoryTransfer\CreateAction;
use App\Actions\InventoryTransfer\Item\DeleteAction as ItemDeleteAction;
use App\Actions\InventoryTransfer\UpdateAction;
use App\Models\Inventory;
use App\Models\InventoryTransfer;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Page extends Component
{
    protected $listeners = [
        'InventoryTransfer-Refresh-Component' => 'refresh',
    ];

    public $fromBranch;

    public $toBranch;

    public $table_id;

    public $inventory_id;

    public $barcode;

    public $inventory_transfer;

    public $inventory_transfers;

    public $items = [];

    public function refresh()
    {
        $this->mount($this->table_id);
    }

    public function mount($table_id = null)
    {
        $this->table_id = $table_id;
        $this->items = [];
        if (! $this->table_id) {
            $this->inventory_transfers = [
                'date' => date('Y-m-d'),
                'branch_id' => session('branch_id'),
                'from_branch_id' => session('branch_id'),
                'to_branch_id' => '',
                'description' => '',
                'status' => 'pending',
            ];
            $this->fromBranch = [
                session('branch_id') => session('branch_name'),
            ];
        } else {
            $this->inventory_transfer = InventoryTransfer::with('fromBranch:id,name', 'toBranch:id,name', 'items.product:id,name')->find($this->table_id);
            if (! $this->inventory_transfer) {
                return redirect()->route('inventory::transfer::index');
            }
            $this->fromBranch = [
                $this->inventory_transfer['from_branch_id'] => $this->inventory_transfer->fromBranch?->name,
            ];
            $this->toBranch = [
                $this->inventory_transfer['to_branch_id'] => $this->inventory_transfer->toBranch?->name,
            ];
            $this->items = $this->inventory_transfer->items->mapWithKeys(function ($item) {
                $key = $item['inventory_id'];

                return [
                    $key => [
                        'id' => $item['id'],
                        'key' => $key,
                        'inventory_id' => $item['inventory_id'],
                        'product_id' => $item['product_id'],
                        'batch' => $item->inventory->batch,
                        'barcode' => $item->inventory->barcode,
                        'current_stock' => $item->inventory->quantity,
                        'name' => $item['name'],
                        'quantity' => round($item['quantity'], 3),
                    ],
                ];
            })->toArray();
            $this->inventory_transfers = $this->inventory_transfer->toArray();
        }
        $this->dispatch('OpenToBranchBox');
    }

    public function updated($key, $value)
    {
        if (preg_match('/^items\..*/', $key)) {
            $indexes = explode('.', $key);
            $index = $indexes[1] ?? null;
            if (! is_numeric($value)) {
                $this->items[$index][$indexes[2]] = 0;
            }
        }
        if ($key == 'inventory_transfers.from_branch_id') {
            foreach ($this->items as $key => $value) {
                $this->removeItem($key);
            }
        }
        if ($key == 'inventory_id') {
            if ($value) {
                $this->selectItem($value);
                $this->inventory_id = '';
            }
        }
        if ($key == 'barcode') {
            $this->getProductByBarcode($value);
            $this->barcode = '';
        }
    }

    public function getProductByBarcode($value)
    {
        $inventory = Inventory::firstWhere('barcode', $value);
        if (! $inventory) {
            // $this->dispatch('error', ['message' => 'No Match Found']);

            return false;
        }
        $this->addToCart($inventory);
    }

    public function selectItem($id)
    {
        if ($id) {
            $inventory = Inventory::find($id);
            $this->addToCart($inventory);
            $this->dispatch('OpenProductBox');
        }
    }

    public function addToCart($inventory)
    {
        $key = $inventory->id;
        $product = $inventory->product;
        $single = [
            'key' => $key,
            'product_id' => $inventory->product_id,
            'branch_id' => $inventory->branch_id,
            'current_stock' => $inventory->quantity,
            'inventory_id' => $inventory->id,
            'batch' => $inventory->batch,
            'barcode' => $inventory->barcode,
            'name' => $product->name,
            'quantity' => 1,
        ];

        if (isset($this->items[$key])) {
            $this->items[$key]['quantity'] += 1;
        } else {
            $this->items[$key] = $single;
        }
    }

    public function removeItem($index)
    {
        try {
            $id = $this->items[$index]['id'] ?? '';
            if ($id) {
                $response = (new ItemDeleteAction())->execute($id);
                if (! $response['success']) {
                    throw new Exception($response['message'], 1);
                }
            }
            unset($this->items[$index]);
            $this->dispatch('success', ['message' => 'item removed successfully']);
        } catch (\Throwable $th) {
            $this->dispatch('error', ['message' => $th->getMessage()]);
        }
    }

    protected $rules = [
        'inventory_transfers.date' => ['required'],
        'inventory_transfers.branch_id' => ['required'],
        'inventory_transfers.from_branch_id' => ['required', 'different:inventory_transfers.to_branch_id'],
        'inventory_transfers.to_branch_id' => ['required'],
    ];

    protected $messages = [
        'inventory_transfers.date.required' => 'The date field is required',
        'inventory_transfers.branch_id' => 'The branch field is required.',
        'inventory_transfers.from_branch_id.required' => 'The from branch field is required.',
        'inventory_transfers.from_branch_id.different' => 'The From Branch field and To Branch must be different',
        'inventory_transfers.to_branch_id.required' => 'The to branch field is required.',
    ];

    public function save($status = 'completed')
    {
        $this->validate();
        try {
            DB::beginTransaction();
            if (count($this->items) == 0) {
                throw new Exception('Please add any item to transfer', 1);
            }
            $this->inventory_transfers['status'] = $status;
            $this->inventory_transfers['items'] = $this->items;
            if (! $this->table_id) {
                $response = (new CreateAction())->execute($this->inventory_transfers, Auth::id());
            } else {
                $response = (new UpdateAction())->execute($this->inventory_transfers, $this->table_id, Auth::id());
            }
            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }
            $this->dispatch('success', ['message' => $response['message']]);
            DB::commit();
            if ($status == 'completed') {
                if ($this->table_id) {
                    return redirect(route('inventory::transfer::view', $this->table_id));
                }
            }
            $this->mount($this->table_id);
            $this->dispatch('SelectDropDownValues', $this->inventory_transfers);
        } catch (\Throwable $e) {
            DB::rollback();
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.inventory-transfer.page');
    }
}
