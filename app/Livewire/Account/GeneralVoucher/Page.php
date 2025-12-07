<?php

namespace App\Livewire\Account\GeneralVoucher;

use App\Actions\Journal\GeneralVoucherJournalEntryAction;
use App\Models\Account;
use App\Models\JournalEntry;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Page extends Component
{
    protected $listeners = [
        'GeneralVoucher-Page-Create-Component' => 'create',
        'GeneralVoucher-Page-Update-Component' => 'edit',
    ];

    public $journals;

    public $table_id;

    public function create()
    {
        $this->mount();
        $this->dispatch('SelectDropDownValues', $this->journals);
        $this->dispatch('ToggleGeneralVoucherModal');
    }

    public function edit($id)
    {
        $this->mount($id);
        $this->dispatch('SelectDropDownValues', $this->journals);
        $this->dispatch('ToggleGeneralVoucherModal');
    }

    public function mount($table_id = null)
    {
        $this->table_id = $table_id;
        if (! $this->table_id) {
            $this->journals = [
                'branch_id' => session('branch_id'),
                'debit_id' => null,
                'debit_name' => null,
                'credit_id' => null,
                'credit_name' => null,
                'source' => 'General Voucher',
                'amount' => 0,
                'date' => date('Y-m-d'),
                'person_name' => null,
                'reference_number' => null,
                'description' => '',
                'remarks' => null,
            ];
        } else {
            // Load JournalEntry with debit > 0 to get the main entry
            $debitEntry = JournalEntry::where('journal_id', $this->table_id)
                ->where('source', 'General Voucher')
                ->where('debit', '>', 0)
                ->with(['account', 'journal.entries.account'])
                ->first();

            if ($debitEntry && $debitEntry->journal) {
                $journal = $debitEntry->journal;
                $this->journals = $journal->toArray();

                // Get debit and credit account IDs from entries
                $creditEntry = $journal->entries->where('credit', '>', 0)->first();

                if ($debitEntry) {
                    $this->journals['debit_id'] = $debitEntry->account_id;
                    $this->journals['debit_name'] = $debitEntry->account->name ?? null;
                }
                if ($creditEntry) {
                    $this->journals['credit_id'] = $creditEntry->account_id;
                    $this->journals['credit_name'] = $creditEntry->account->name ?? null;
                }
                if ($debitEntry) {
                    $this->journals['amount'] = $debitEntry->debit;
                }
            }
        }
    }

    public function updated($key, $value)
    {
        if ($key == 'journals.debit_id') {
            $this->journals['debit_name'] = Account::find($value)?->name;
        }
        if ($key == 'journals.credit_id') {
            $this->journals['credit_name'] = Account::find($value)?->name;
        }
    }

    protected function rules()
    {
        return [
            'journals.debit_id' => ['required'],
            'journals.credit_id' => ['required'],
            'journals.amount' => ['required', 'numeric', 'min:1', 'max:999999'],
            'journals.date' => ['required', 'date'],
            'journals.person_name' => ['max:100'],
            'journals.reference_number' => ['max:100'],
            'journals.description' => ['required', 'max:255'],
            'journals.remarks' => ['max:255'],
        ];
    }

    protected function messages()
    {
        return [
            'journals.debit_id.required' => 'The Debit Head field is required.',
            'journals.credit_id.required' => 'The Credit Head field is required.',
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
            $response = (new GeneralVoucherJournalEntryAction())->execute($userId, $this->journals, $this->table_id);
            if (! $response['success']) {
                throw new Exception($response['message'], 1);
            }
            $this->dispatch('success', ['message' => $response['message']]);
            $this->mount($this->table_id);
            $this->dispatch('SelectDropDownValues', $journals);
            DB::commit();
            if (! $close) {
                $this->dispatch('ToggleGeneralVoucherModal');
            } else {
                $this->mount();
            }
            $this->dispatch('RefreshGeneralVoucherTable');
        } catch (Exception $e) {
            DB::rollback();
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.account.general-voucher.page');
    }
}
