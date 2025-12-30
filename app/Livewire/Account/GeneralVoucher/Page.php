<?php

namespace App\Livewire\Account\GeneralVoucher;

use App\Actions\Journal\GeneralVoucherJournalEntryAction;
use App\Models\Account;
use App\Models\Journal;
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

    public $entries = [];

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
                'source' => 'General Voucher',
                'date' => date('Y-m-d'),
                'person_name' => null,
                'reference_number' => null,
                'remarks' => null,
            ];
            $this->entries = [
                [
                    'key' => uniqid(),
                    'account_id' => null,
                    'debit' => 0,
                    'credit' => 0,
                    'description' => '',
                    'person_name' => null,
                ],
                [
                    'key' => uniqid(),
                    'account_id' => null,
                    'debit' => 0,
                    'credit' => 0,
                    'description' => '',
                    'person_name' => null,
                ],
            ];
        } else {
            // Load Journal with all entries
            $journal = Journal::where('id', $this->table_id)
                ->where('source', 'General Voucher')
                ->with(['entries.account'])
                ->first();

            if ($journal) {
                $this->journals = $journal->toArray();
                $this->entries = [];

                foreach ($journal->entries as $entry) {
                    $this->entries[] = [
                        'key' => uniqid(),
                        'account_id' => $entry->account_id,
                        'account_name' => $entry->account->name ?? null,
                        'debit' => $entry->debit,
                        'credit' => $entry->credit,
                        'description' => $entry->description ?? '',
                        'person_name' => $entry->person_name ?? null,
                    ];
                }
            }
        }
    }

    public function addEntry()
    {
        $this->entries[] = [
            'key' => uniqid(),
            'account_id' => null,
            'debit' => 0,
            'credit' => 0,
            'description' => $this->journals['description'] ?? '',
            'name' => null,
        ];
        $this->dispatch('reinitialize-selects');
    }

    public function removeEntry($key)
    {
        $this->entries = array_filter($this->entries, function ($entry) use ($key) {
            return $entry['key'] !== $key;
        });
        $this->entries = array_values($this->entries);
    }

    public function updated($key, $value)
    {
        // Update account name when account_id changes in entries
        if (str_starts_with($key, 'entries.')) {
            $parts = explode('.', $key);
            $index = (int) $parts[1];
            // Reset credit and debit if they have non-zero numeric values
            if (isset($this->entries[$index])) {
                if (! is_numeric($this->entries[$index]['debit'])) {
                    $this->entries[$index]['debit'] = 0;
                }
                if (! is_numeric($this->entries[$index]['credit'])) {
                    $this->entries[$index]['credit'] = 0;
                }
            }
        }

        // Sync description to all entries when main description changes
        if ($key === 'journals.description') {
            foreach ($this->entries as $index => &$entry) {
                if (empty($entry['description'])) {
                    $entry['description'] = $value;
                }
            }
        }
    }

    protected function rules()
    {
        $rules = [
            'journals.date' => ['required', 'date'],
            'journals.person_name' => ['max:100'],
            'journals.reference_number' => ['max:100'],
            'journals.remarks' => ['max:255'],
            'entries' => ['required', 'array', 'min:2'],
            'entries.*.account_id' => ['required'],
            'entries.*.debit' => ['required', 'numeric', 'min:0'],
            'entries.*.credit' => ['required', 'numeric', 'min:0'],
            'entries.*.person_name' => ['max:100'],
            'entries.*.description' => ['max:255'],
        ];

        return $rules;
    }

    protected function messages()
    {
        return [
            'journals.date.required' => 'The Date field is required.',
            'journals.description.required' => 'The Description field is required.',
            'entries.required' => 'At least two journal entries are required.',
            'entries.min' => 'At least two journal entries are required.',
            'entries.*.account_id.required' => 'The Account field is required for all entries.',
            'entries.*.debit.required' => 'The Debit field is required.',
            'entries.*.credit.required' => 'The Credit field is required.',
        ];
    }

    public function save($close = false)
    {
        // Custom validation: each entry cannot have both debit and credit > 0
        foreach ($this->entries as $index => $entry) {
            if ($entry['debit'] > 0 && $entry['credit'] > 0) {
                $this->dispatch('error', ['message' => 'Entry #'.($index + 1).' cannot have both debit and credit amounts. Each entry must be either a debit or a credit, not both.']);

                return;
            }
        }

        // Custom validation: total debits must equal total credits
        $totalDebits = array_sum(array_column($this->entries, 'debit'));
        $totalCredits = array_sum(array_column($this->entries, 'credit'));

        if (abs($totalDebits - $totalCredits) > 0.01) {
            $this->dispatch('error', ['message' => 'Total debits must equal total credits.']);

            return;
        }

        // Custom validation: at least one entry with debit > 0 and one with credit > 0
        $hasDebit = false;
        $hasCredit = false;
        foreach ($this->entries as $entry) {
            if ($entry['debit'] > 0) {
                $hasDebit = true;
            }
            if ($entry['credit'] > 0) {
                $hasCredit = true;
            }
        }

        if (! $hasDebit || ! $hasCredit) {
            $this->dispatch('error', ['message' => 'At least one entry must have a debit amount and one must have a credit amount.']);

            return;
        }

        $this->validate();
        try {
            DB::beginTransaction();
            $userId = Auth::id();

            // Prepare entries data for the action
            $entriesData = [];
            foreach ($this->entries as $entry) {
                if ($entry['account_id'] && ($entry['debit'] > 0 || $entry['credit'] > 0)) {
                    $entriesData[] = [
                        'account_id' => $entry['account_id'],
                        'counter_account_id' => null, // Will be set based on other entries
                        'debit' => $entry['debit'] ?? 0,
                        'credit' => $entry['credit'] ?? 0,
                        'created_by' => $userId,
                        'description' => $entry['description'] ?? null,
                        'person_name' => $entry['person_name'] ?? null,
                    ];
                }
            }

            $this->journals['entries'] = $entriesData;
            $response = (new GeneralVoucherJournalEntryAction())->execute($userId, $this->journals, $this->table_id);
            if (! $response['success']) {
                throw new Exception($response['message'], 1);
            }
            $this->dispatch('success', ['message' => $response['message']]);
            $this->mount($this->table_id);
            $this->dispatch('SelectDropDownValues', $this->journals);
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
