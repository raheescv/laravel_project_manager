<?php

namespace App\Livewire\Account\Expense;

use App\Actions\Journal\GeneralExpenseJournalEntryAction;
use App\Models\Account;
use App\Models\Configuration;
use App\Models\Journal;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Page extends Component
{
    protected $listeners = [
        'Expense-Page-Create-Component' => 'create',
        'Expense-Page-Update-Component' => 'edit',
    ];

    public $existingExpenses = [];

    public $journals;

    public $parents;

    public $table_id;

    public $default_payment_method_id;

    public $paymentMethods = [];

    public function create()
    {
        $this->mount();
        $this->dispatch('SelectDropDownValues', $this->journals);
        $this->dispatch('ToggleExpenseModal');
    }

    public function edit($id)
    {
        $this->mount($id);
        $this->dispatch('ToggleExpenseModal');
    }

    public function mount($table_id = null)
    {
        $this->default_payment_method_id = Configuration::where('key', 'default_payment_method_id')->value('value') ?? 1;
        $this->paymentMethods = Account::where('id', $this->default_payment_method_id)->pluck('name', 'id')->toArray();
        $this->table_id = $table_id;
        if (! $this->table_id) {
            $this->journals = [
                'branch_id' => session('branch_id'),
                'debit' => null,
                'debit_name' => null,
                'source' => 'expense',
                'credit' => $this->default_payment_method_id,
                'credit_name' => $this->default_payment_method_id ? Account::find($this->default_payment_method_id)->name : null,
                'amount' => 0,
                'date' => date('Y-m-d'),
                'person_name' => null,
                'reference_number' => null,
                'description' => '',
                'remarks' => null,
            ];
        } else {
            $journal = Journal::find($this->table_id);
            $this->journals = $journal->toArray();
        }
    }

    public function updated($key, $value)
    {
        if ($key == 'journals.debit') {
            $this->journals['debit_name'] = Account::find($value)?->name;
        }
        if ($key == 'journals.credit') {
            $this->journals['credit_name'] = Account::find($value)?->name;
        }
    }

    protected function rules()
    {
        return [
            'journals.debit' => ['required'],
            'journals.credit' => ['required'],
            'journals.amount' => ['required', 'numeric', 'min:1', 'max:999999'],
            'journals.date' => ['required', 'date'],
            'journals.person_name' => ['max:30'],
            'journals.reference_number' => ['max:30'],
            'journals.description' => ['required', 'max:100'],
            'journals.remarks' => ['max:100'],
        ];
    }

    protected function messages()
    {
        return [
            'journals.debit.required' => 'The Expense Category field is required.',
            'journals.credit.required' => 'The Payment Method field is required.',
            'journals.date.required' => 'The Date field is required.',
            'journals.description.required' => 'The Description field is required.',
            'journals.amount.required' => 'The Amount field is required.',
            'journals.amount.min' => 'The Amount must be at least 1.',
            'journals.amount.max' => 'The Amount may not be greater than '.currency('999999').'.',
        ];
    }

    public function save($close = false)
    {
        $this->validate();
        try {
            DB::beginTransaction();
            $userId = Auth::id();
            $journals = $this->journals;
            $response = (new GeneralExpenseJournalEntryAction())->execute($userId, $this->journals, $this->table_id);
            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }
            $this->dispatch('success', ['message' => $response['message']]);
            $this->mount($this->table_id);
            $this->dispatch('SelectDropDownValues', $journals);
            DB::commit();
            if (! $close) {
                $this->dispatch('ToggleExpenseModal');
            } else {
                $this->mount();
            }
            $this->dispatch('RefreshExpenseTable');
        } catch (\Throwable $e) {
            DB::rollback();
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.account.expense.page');
    }
}
