<?php

namespace App\Livewire\User\EmployeeCommission;

use App\Actions\EmployeeCommission\BulkAssignAction;
use Exception;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class BulkPage extends Component
{
    protected $listeners = [
        'EmployeeCommission-Page-BulkAssign-Component' => 'create',
    ];

    public $employee_id;

    public $commission_percentage;

    public $overwrite = false;

    public $department_ids = [];

    public $main_category_ids = [];

    public $sub_category_ids = [];

    public $brand_ids = [];

    protected function rules()
    {
        return [
            'employee_id' => ['required', 'exists:users,id'],
            'commission_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }

    protected function validationAttributes()
    {
        return [
            'employee_id' => 'employee',
            'commission_percentage' => 'commission percentage',
        ];
    }

    protected $messages = [
        'employee_id.required' => 'The employee field is required',
        'employee_id.exists' => 'The selected employee is invalid',
        'commission_percentage.required' => 'The commission percentage field is required',
        'commission_percentage.numeric' => 'The commission percentage must be a number',
        'commission_percentage.min' => 'The commission percentage must be at least 0',
        'commission_percentage.max' => 'The commission percentage must not exceed 100',
    ];

    public function create()
    {
        $this->reset(['employee_id', 'commission_percentage', 'overwrite', 'department_ids', 'main_category_ids', 'sub_category_ids', 'brand_ids']);
        $this->resetErrorBag();
        $this->dispatch('ResetBulkDropDownValues');
        $this->dispatch('ToggleEmployeeCommissionBulkModal');
    }

    protected function filters(): array
    {
        return [
            'department_ids' => array_filter((array) $this->department_ids),
            'main_category_ids' => array_filter((array) $this->main_category_ids),
            'sub_category_ids' => array_filter((array) $this->sub_category_ids),
            'brand_ids' => array_filter((array) $this->brand_ids),
        ];
    }

    public function getMatchCountProperty(): int
    {
        return (new BulkAssignAction())->productQuery($this->filters())->count();
    }

    public function getHasFiltersProperty(): bool
    {
        foreach ($this->filters() as $value) {
            if (! empty($value)) {
                return true;
            }
        }

        return false;
    }

    public function save()
    {
        abort_unless(Auth::user()?->can('employee commission.create'), 403);
        $this->validate();

        try {
            $payload = array_merge($this->filters(), [
                'employee_id' => $this->employee_id,
                'commission_percentage' => $this->commission_percentage,
                'overwrite' => $this->overwrite,
            ]);

            $response = (new BulkAssignAction())->execute($payload);
            if (! $response['success']) {
                throw new Exception($response['message'], 1);
            }

            $this->dispatch('success', ['message' => $response['message']]);
            $this->dispatch('ToggleEmployeeCommissionBulkModal');
            $this->dispatch('RefreshEmployeeCommissionTable');
            $this->create();
        } catch (Exception $e) {
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.user.employee-commission.bulk-page');
    }
}
