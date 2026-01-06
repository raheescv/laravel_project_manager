<?php

namespace App\Livewire\User\EmployeeCommission;

use App\Actions\EmployeeCommission\CreateAction;
use App\Actions\EmployeeCommission\UpdateAction;
use App\Models\EmployeeCommission;
use Exception;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Page extends Component
{
    protected $listeners = [
        'EmployeeCommission-Page-Create-Component' => 'create',
        'EmployeeCommission-Page-Update-Component' => 'edit',
    ];

    public $employee_commissions;

    public $table_id;

    protected function validationAttributes()
    {
        return [
            'employee_commissions.product_id' => 'product',
            'employee_commissions.employee_id' => 'employee',
            'employee_commissions.commission_percentage' => 'commission percentage',
        ];
    }

    public function create()
    {
        $this->mount();
        $this->dispatch('ResetDropDownValues');
        $this->dispatch('ToggleEmployeeCommissionModal');
    }

    public function edit($id)
    {
        $this->mount($id);
        $this->dispatch('ToggleEmployeeCommissionModal');
    }

    public function mount($table_id = null)
    {
        $this->table_id = $table_id;
        if (! $this->table_id) {
            $this->employee_commissions = [
                'product_id' => '',
                'employee_id' => '',
                'commission_percentage' => '',
            ];
        } else {
            $employeeCommission = EmployeeCommission::with('product', 'employee:id,name')->find($this->table_id);
            $this->employee_commissions = $employeeCommission->toArray();
        }
        $this->dispatch('SelectDropDownValues', $this->employee_commissions);
    }

    protected function rules()
    {
        $employeeId = $this->employee_commissions['employee_id'] ?? null;

        $uniqueRule = Rule::unique('employee_commissions', 'product_id')
            ->where('employee_id', $employeeId);

        if ($this->table_id) {
            $uniqueRule->ignore($this->table_id);
        }

        return [
            'employee_commissions.product_id' => ['required', 'exists:products,id', $uniqueRule],
            'employee_commissions.employee_id' => ['required', 'exists:users,id'],
            'employee_commissions.commission_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }

    protected $messages = [
        'employee_commissions.product_id.required' => 'The product field is required',
        'employee_commissions.product_id.unique' => 'This commission configuration already exists for this employee and product',
        'employee_commissions.product_id.exists' => 'The selected product is invalid',
        'employee_commissions.employee_id.required' => 'The employee field is required',
        'employee_commissions.employee_id.exists' => 'The selected employee is invalid',
        'employee_commissions.commission_percentage.required' => 'The commission percentage field is required',
        'employee_commissions.commission_percentage.numeric' => 'The commission percentage must be a number',
        'employee_commissions.commission_percentage.min' => 'The commission percentage must be at least 0',
        'employee_commissions.commission_percentage.max' => 'The commission percentage must not exceed 100',
    ];

    public function save($close = false)
    {
        $this->validate();

        // Additional custom validation for unique combination
        $employeeId = $this->employee_commissions['employee_id'] ?? null;
        $productId = $this->employee_commissions['product_id'] ?? null;

        if ($employeeId && $productId) {
            $exists = EmployeeCommission::where('employee_id', $employeeId)
                ->where('product_id', $productId)
                ->when($this->table_id, function ($query) {
                    return $query->where('id', '!=', $this->table_id);
                })
                ->exists();

            if ($exists) {
                $this->addError('product_id', 'This commission configuration already exists for this employee and product');

                return;
            }
        }

        try {
            if (! $this->table_id) {
                $response = (new CreateAction())->execute($this->employee_commissions);
            } else {
                $response = (new UpdateAction())->execute($this->employee_commissions, $this->table_id);
            }
            if (! $response['success']) {
                throw new Exception($response['message'], 1);
            }
            if (! $this->table_id && isset($response['data']['id'])) {
                $this->table_id = $response['data']['id'];
            }
            $this->dispatch('success', ['message' => $response['message']]);
            if (! $close) {
                $this->mount($this->table_id);
                $this->dispatch('ToggleEmployeeCommissionModal');
            } else {
                $this->dispatch('ResetDropDownValues');
                $this->mount();
            }
            $this->dispatch('RefreshEmployeeCommissionTable');
        } catch (Exception $e) {
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.user.employee-commission.page');
    }
}
