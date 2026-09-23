<?php

namespace App\Livewire\EmployeeInventory;

use App\Actions\EmployeeInventory\TransferAction;
use App\Models\Branch;
use App\Models\Inventory;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

/**
 * "Dispatch Desk" — hand several products from branch stock to one employee in a
 * single pass: pick the person, build the basket, give a reason, transfer.
 *
 * Each basket line still goes through {@see TransferAction} one at a time (the same
 * path the inventory view's modal uses), so inventory logs and remarks stay
 * identical; the loop only adds one transaction around the whole hand-over.
 *
 * Design: docs/inventory-employee-transfer-preview.html (direction A).
 */
class Page extends Component
{
    /** How many rows a search dropdown offers before the user must narrow it. */
    private const SEARCH_LIMIT = 12;

    public $employee_id = '';

    /** @var array{id:int,name:string,code:?string,mobile:?string,designation:?string,photo:string}|null */
    public ?array $employee = null;

    public string $employeeSearch = '';

    public string $productSearch = '';

    /**
     * Basket lines, keyed by inventory id.
     *
     * @var array<int, array{inventory_id:int,name:string,barcode:?string,batch:?string,available:float,cost:float,quantity:float}>
     */
    public array $items = [];

    public string $reason = '';

    public $branch_id;

    public function mount(): void
    {
        abort_unless(Auth::user()->can('inventory.transfer'), 403);

        $this->branch_id = session('branch_id');
    }

    protected function rules(): array
    {
        return [
            'employee_id' => ['required', 'exists:users,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'reason' => ['required', 'string', 'min:3'],
        ];
    }

    protected function messages(): array
    {
        return [
            'employee_id.required' => 'Please select the employee receiving the stock',
            'items.required' => 'Add at least one product to transfer',
            'items.min' => 'Add at least one product to transfer',
            'items.*.quantity.required' => 'Enter a quantity for every product',
            'items.*.quantity.min' => 'Quantity must be greater than 0',
            'reason.required' => 'A reason is required — it is written to the inventory log',
            'reason.min' => 'Reason must be at least 3 characters',
        ];
    }

    public function selectEmployee($employeeId): void
    {
        $employee = $this->employeeQuery()->find($employeeId);
        if (! $employee) {
            $this->dispatch('error', ['message' => 'Employee not found in this branch']);

            return;
        }

        $this->employee_id = $employee->id;
        $this->employee = [
            'id' => $employee->id,
            'name' => $employee->name,
            'code' => $employee->code,
            'mobile' => $employee->mobile,
            'designation' => $employee->designation?->name,
            'photo' => $employee->photo_url,
        ];
        $this->employeeSearch = '';
        $this->resetErrorBag('employee_id');
    }

    public function clearEmployee(): void
    {
        $this->employee_id = '';
        $this->employee = null;
        $this->employeeSearch = '';
    }

    public function addItem($inventoryId): void
    {
        $inventoryId = (int) $inventoryId;
        $this->productSearch = '';

        if (isset($this->items[$inventoryId])) {
            $this->changeQuantity($inventoryId, $this->items[$inventoryId]['quantity'] + 1);

            return;
        }

        $inventory = $this->availableStockQuery()->where('inventories.id', $inventoryId)->first();
        if (! $inventory) {
            $this->dispatch('error', ['message' => 'That stock row is no longer available in this branch']);

            return;
        }

        $this->items[$inventoryId] = [
            'inventory_id' => (int) $inventory->id,
            'name' => $inventory->name,
            'barcode' => $inventory->barcode,
            'batch' => $inventory->batch,
            'available' => (float) $inventory->quantity,
            'cost' => (float) $inventory->cost,
            'quantity' => 1,
        ];
        $this->resetErrorBag('items');
    }

    public function increment($inventoryId): void
    {
        if (isset($this->items[$inventoryId])) {
            $this->changeQuantity($inventoryId, $this->items[$inventoryId]['quantity'] + 1);
        }
    }

    public function decrement($inventoryId): void
    {
        if (isset($this->items[$inventoryId])) {
            $this->changeQuantity($inventoryId, $this->items[$inventoryId]['quantity'] - 1);
        }
    }

    public function removeItem($inventoryId): void
    {
        unset($this->items[$inventoryId]);
    }

    public function updatedItems(): void
    {
        foreach (array_keys($this->items) as $inventoryId) {
            $this->changeQuantity($inventoryId, $this->items[$inventoryId]['quantity']);
        }
    }

    public function setReason(string $reason): void
    {
        $this->reason = $reason;
        $this->resetErrorBag('reason');
    }

    /**
     * Hand the whole basket over. One transaction so a failure on line three does
     * not leave lines one and two already moved.
     */
    public function transfer(): void
    {
        $this->validate();

        try {
            DB::transaction(function (): void {
                foreach ($this->items as $line) {
                    $response = (new TransferAction())->execute([
                        'inventory_id' => $line['inventory_id'],
                        'employee_id' => $this->employee_id,
                        'quantity' => $line['quantity'],
                        'reason' => $this->reason,
                    ], Auth::id());

                    if (! $response['success']) {
                        throw new \Exception($line['name'].': '.$response['message'], 1);
                    }
                }
            });

            $this->dispatch('success', ['message' => count($this->items).' product(s) transferred to '.$this->employee['name']]);
            $this->reset(['items', 'reason', 'productSearch']);
        } catch (\Throwable $e) {
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    /**
     * Send one of the employee's holdings back to branch stock, in full.
     */
    public function returnToBranch($inventoryId): void
    {
        $inventory = Inventory::query()
            ->where('branch_id', $this->branch_id)
            ->where('employee_id', $this->employee_id)
            ->where('id', $inventoryId)
            ->first();

        if (! $inventory || $inventory->quantity <= 0) {
            $this->dispatch('error', ['message' => 'Nothing left to return on that line']);

            return;
        }

        try {
            $response = (new TransferAction())->execute([
                'inventory_id' => $inventory->id,
                'quantity' => $inventory->quantity,
                'reason' => trim($this->reason) !== '' ? $this->reason : 'Returned to branch stock',
            ], Auth::id());

            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }

            $this->dispatch('success', ['message' => $response['message']]);
        } catch (\Throwable $e) {
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.employee-inventory.page', [
            'branch' => Branch::find($this->branch_id),
            'employees' => $this->employeeOptions(),
            'stock' => $this->stockOptions(),
            'holdings' => $this->holdings(),
        ]);
    }

    /**
     * Clamp an edited quantity to what the branch actually holds; the action
     * refuses anything larger anyway, so the UI should never let it be typed.
     */
    private function changeQuantity($inventoryId, $quantity): void
    {
        $line = $this->items[$inventoryId] ?? null;
        if (! $line) {
            return;
        }

        $quantity = (float) $quantity;
        if ($quantity < 0.001) {
            $quantity = 0.001;
        }
        if ($quantity > $line['available']) {
            $quantity = $line['available'];
            $this->dispatch('error', ['message' => $line['name'].': only '.$line['available'].' available in this branch']);
        }

        $this->items[$inventoryId]['quantity'] = $quantity;
    }

    private function employeeQuery()
    {
        return User::query()
            ->with('designation:id,name')
            ->where('type', 'employee')
            ->active()
            ->whereHas('branches', function ($query): void {
                $query->where('user_has_branches.branch_id', $this->branch_id);
            });
    }

    private function employeeOptions(): Collection
    {
        if ($this->employee_id) {
            return collect();
        }

        return $this->employeeQuery()
            ->withCount(['inventories' => function ($query): void {
                $query->where('branch_id', $this->branch_id)->where('quantity', '>', 0);
            }])
            ->when($this->employeeSearch, function ($query, $value) {
                return $query->where(function ($q) use ($value): void {
                    $value = trim($value);
                    $q->where('name', 'like', "%{$value}%")
                        ->orWhere('code', 'like', "%{$value}%")
                        ->orWhere('mobile', 'like', "%{$value}%")
                        ->orWhere('email', 'like', "%{$value}%");
                });
            })
            ->orderBy('name')
            ->limit(self::SEARCH_LIMIT)
            ->get();
    }

    /** Branch stock — never another employee's holdings — with something left on it. */
    private function availableStockQuery()
    {
        return Inventory::query()
            ->join('products', 'inventories.product_id', '=', 'products.id')
            ->where('inventories.branch_id', $this->branch_id)
            ->whereNull('inventories.employee_id')
            ->where('inventories.quantity', '>', 0)
            ->select(
                'inventories.id',
                'inventories.barcode',
                'inventories.batch',
                'inventories.quantity',
                'inventories.cost',
                'products.name',
                'products.code',
            );
    }

    private function stockOptions(): Collection
    {
        return $this->availableStockQuery()
            ->when($this->productSearch, function ($query, $value) {
                return $query->where(function ($q) use ($value): void {
                    $value = trim($value);
                    $q->where('products.name', 'like', "%{$value}%")
                        ->orWhere('products.name_arabic', 'like', "%{$value}%")
                        ->orWhere('products.code', 'like', "%{$value}%")
                        ->orWhere('inventories.barcode', 'like', "%{$value}%")
                        ->orWhere('inventories.batch', 'like', "%{$value}%");
                });
            })
            ->whereNotIn('inventories.id', array_keys($this->items))
            ->orderBy('products.name')
            ->limit(self::SEARCH_LIMIT)
            ->get();
    }

    /** What the selected employee is already accountable for in this branch. */
    private function holdings(): Collection
    {
        if (! $this->employee_id) {
            return collect();
        }

        return Inventory::query()
            ->join('products', 'inventories.product_id', '=', 'products.id')
            ->where('inventories.branch_id', $this->branch_id)
            ->where('inventories.employee_id', $this->employee_id)
            ->where('inventories.quantity', '>', 0)
            ->select(
                'inventories.id',
                'inventories.barcode',
                'inventories.batch',
                'inventories.quantity',
                'inventories.cost',
                'inventories.updated_at',
                'products.name',
            )
            ->orderBy('products.name')
            ->get();
    }
}
