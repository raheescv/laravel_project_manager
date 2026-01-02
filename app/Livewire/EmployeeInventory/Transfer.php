<?php

namespace App\Livewire\EmployeeInventory;

use App\Actions\EmployeeInventory\TransferAction;
use App\Models\Inventory;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Transfer extends Component
{
    public $inventory_id;

    public $employee_id;

    public $return_to_main_branch;

    public $quantity;

    public $reason;

    public $inventory;

    protected $listeners = [
        'EmployeeInventory-Transfer-Component' => 'openModal',
    ];

    public function openModal($inventoryId)
    {
        // Handle Livewire 3 event data - can be array with key or direct value
        if (is_array($inventoryId) && isset($inventoryId['inventoryId'])) {
            $inventoryId = $inventoryId['inventoryId'];
        }

        $this->inventory_id = $inventoryId;
        $this->inventory = Inventory::find($inventoryId);

        if (! $this->inventory) {
            $this->dispatch('error', ['message' => 'Inventory not found']);

            return;
        }

        // Ensure it's branch inventory
        if ($this->inventory->employee_id !== null) {
            // $this->dispatch('error', ['message' => 'Cannot transfer from employee inventory. Please select branch inventory.']);

            // return;
        }

        // Reset form
        $this->employee_id = '';
        $this->quantity = '';
        $this->reason = '';

        $this->dispatch('ToggleEmployeeInventoryTransferModal');
    }

    protected function rules()
    {
        return [
            'inventory_id' => ['required', 'exists:inventories,id'],
            'employee_id' => ['required_if:return_to_main_branch,false', 'exists:users,id'],
            'quantity' => ['required', 'numeric', 'min:0.001'],
            'reason' => ['required', 'string', 'min:3'],
        ];
    }

    protected $messages = [
        'inventory_id.required' => 'Inventory ID is required',
        'inventory_id.exists' => 'Selected inventory does not exist',
        'employee_id.required' => 'Please select an employee',
        'employee_id.exists' => 'Selected employee does not exist',
        'quantity.required' => 'Quantity is required',
        'quantity.numeric' => 'Quantity must be a number',
        'quantity.min' => 'Quantity must be greater than 0',
        'reason.required' => 'Reason is required',
        'reason.min' => 'Reason must be at least 3 characters',
    ];

    public function updatedQuantity()
    {
        if ($this->inventory && $this->quantity) {
            if ($this->quantity > $this->inventory->quantity) {
                $this->addError('quantity', 'Quantity cannot exceed available stock: '.$this->inventory->quantity);
            }
        }
    }

    public function updatedReturnToMainBranch()
    {
        if ($this->return_to_main_branch) {
            $this->employee_id = '';
        }
    }

    public function transfer()
    {
        $this->validate();

        try {
            // Refresh inventory to get latest quantity
            $this->inventory = Inventory::find($this->inventory_id);

            if (! $this->inventory) {
                throw new \Exception('Inventory not found', 1);
            }

            // if ($this->inventory->employee_id !== null) {
            //     throw new \Exception('Cannot transfer from employee inventory. Please select branch inventory.', 1);
            // }

            if ($this->quantity > $this->inventory->quantity) {
                throw new \Exception('Insufficient quantity available. Available: '.$this->inventory->quantity, 1);
            }

            $data = [
                'inventory_id' => $this->inventory_id,
                'employee_id' => $this->employee_id,
                'quantity' => $this->quantity,
                'reason' => $this->reason,
            ];

            $response = (new TransferAction())->execute($data, Auth::id());

            if ($response['success']) {
                $this->dispatch('success', ['message' => $response['message']]);
                $this->dispatch('Inventory-Refresh-Component');
                $this->reset(['inventory_id', 'employee_id', 'quantity', 'reason', 'inventory']);
                $this->dispatch('ToggleEmployeeInventoryTransferModal');
            } else {
                throw new \Exception($response['message'], 1);
            }
        } catch (\Throwable $e) {
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.employee-inventory.transfer');
    }
}
