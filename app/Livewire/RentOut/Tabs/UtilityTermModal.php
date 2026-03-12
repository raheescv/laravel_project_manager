<?php

namespace App\Livewire\RentOut\Tabs;

use App\Actions\RentOut\Utility\Term\CreateAction;
use App\Actions\RentOut\Utility\Term\UpdateAction;
use App\Models\RentOut;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class UtilityTermModal extends Component
{
    public bool $showModal = false;

    public ?int $editingId = null;

    public array $form = [
        'rent_out_id' => null,
        'utility_id'  => null,
        'amount'      => 0,
        'balance'     => 0,
        'date'        => '',
        'remarks'     => '',
    ];

    public string $fromDate = '';

    public int $noOfTerms = 2;

    public array $generatedTerms = [];

    public array $utilities = [];

    #[On('open-utility-term-modal')]
    public function openModal($form = [], $editingId = null, $utilities = [])
    {
        $this->form = [
            'rent_out_id' => $form['rent_out_id'] ?? null,
            'utility_id'  => $form['utility_id'] ?? null,
            'amount'      => $form['amount'] ?? 0,
            'balance'     => $form['balance'] ?? 0,
            'date'        => $form['date'] ?? now()->format('Y-m-d'),
            'remarks'     => $form['remarks'] ?? '',
        ];
        $this->editingId = $editingId;
        $this->utilities = $utilities;
        $this->fromDate = $form['from_date'] ?? now()->format('Y-m-d');
        $this->noOfTerms = 2;
        $this->generatedTerms = [];
        $this->resetValidation();
        $this->showModal = true;
    }

    public function generate()
    {
        if (! $this->form['utility_id']) {
            $this->dispatch('error', message: 'Please select a utility.');

            return;
        }

        if (! $this->fromDate) {
            $this->dispatch('error', message: 'Please select a from date.');

            return;
        }

        $startPeriod = Carbon::parse($this->fromDate);
        $this->generatedTerms = [];

        for ($i = 0; $i < $this->noOfTerms; $i++) {
            $this->generatedTerms[] = [
                'rent_out_id' => $this->form['rent_out_id'],
                'utility_id'  => $this->form['utility_id'],
                'date'        => $startPeriod->copy()->addMonths($i)->format('Y-m-d'),
                'amount'      => $this->form['amount'],
            ];
        }
    }

    public function deleteGeneratedTerm($index)
    {
        unset($this->generatedTerms[$index]);
        $this->generatedTerms = array_values($this->generatedTerms);
    }

    public function save()
    {
        // If editing a single term
        if ($this->editingId) {
            return $this->saveSingle();
        }

        // If generated terms exist, save them all
        if (! empty($this->generatedTerms)) {
            return $this->saveGenerated();
        }

        // Otherwise save as single term
        return $this->saveSingle();
    }

    protected function saveSingle()
    {
        $this->validate([
            'form.utility_id' => 'required',
            'form.amount'     => 'required|numeric|min:0.01',
        ], [
            'form.utility_id.required' => 'Please select a utility.',
            'form.amount.required'     => 'Amount is required.',
            'form.amount.min'          => 'Amount must be greater than zero.',
        ]);

        $rentOut = RentOut::find($this->form['rent_out_id']);

        $data = [
            'rent_out_id' => $this->form['rent_out_id'],
            'utility_id'  => $this->form['utility_id'],
            'amount'      => $this->form['amount'],
            'balance'     => $this->form['balance'] ?? 0,
            'date'        => $this->form['date'],
            'remarks'     => $this->form['remarks'] ?? '',
            'tenant_id'   => $rentOut->tenant_id,
            'branch_id'   => $rentOut->branch_id,
            'created_by'  => $rentOut->created_by,
        ];

        try {
            DB::beginTransaction();
            if ($this->editingId) {
                $response = (new UpdateAction)->execute($data, $this->editingId);
            } else {
                $response = (new CreateAction)->execute($data);
            }
            if (! $response['success']) {
                throw new \Exception($response['message']);
            }
            DB::commit();
            $this->showModal = false;
            $this->dispatch('rent-out-updated');
            $this->dispatch('success', message: $response['message']);
        } catch (\Exception $e) {
            DB::rollback();
            $this->dispatch('error', message: $e->getMessage());
        }
    }

    protected function saveGenerated()
    {
        $rentOut = RentOut::find($this->form['rent_out_id']);

        try {
            DB::beginTransaction();
            $action = new CreateAction;
            foreach ($this->generatedTerms as $term) {
                $data = [
                    'rent_out_id' => $term['rent_out_id'],
                    'utility_id'  => $term['utility_id'],
                    'amount'      => $term['amount'],
                    'balance'     => $term['amount'],
                    'date'        => $term['date'],
                    'remarks'     => '',
                    'tenant_id'   => $rentOut->tenant_id,
                    'branch_id'   => $rentOut->branch_id,
                    'created_by'  => $rentOut->created_by,
                ];
                $response = $action->execute($data);
                if (! $response['success']) {
                    throw new \Exception($response['message']);
                }
            }
            DB::commit();
            $this->showModal = false;
            $this->dispatch('rent-out-updated');
            $this->dispatch('success', message: 'Utility terms generated successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            $this->dispatch('error', message: $e->getMessage());
        }
    }

    public function close()
    {
        $this->showModal = false;
    }

    public function render()
    {
        return view('livewire.rent-out.tabs.utility-term-modal');
    }
}
