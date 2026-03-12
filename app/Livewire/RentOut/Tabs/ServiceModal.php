<?php

namespace App\Livewire\RentOut\Tabs;

use App\Actions\RentOut\Service\CreateAction;
use App\Actions\RentOut\Service\UpdateAction;
use App\Models\RentOut;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class ServiceModal extends Component
{
    public bool $showModal = false;

    public ?int $editingId = null;

    public array $form = [
        'rent_out_id' => null,
        'name' => '',
        'amount' => 0,
        'description' => '',
    ];

    #[On('open-service-modal')]
    public function openModal($form = [], $editingId = null)
    {
        $this->form = [
            'rent_out_id' => $form['rent_out_id'] ?? null,
            'name' => $form['name'] ?? '',
            'amount' => $form['amount'] ?? 0,
            'description' => $form['description'] ?? '',
        ];
        $this->editingId = $editingId;
        $this->resetValidation();
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate([
            'form.name' => 'required|string|max:255',
            'form.amount' => 'required|numeric|min:0.01',
        ], [
            'form.name.required' => 'Service name is required.',
            'form.amount.required' => 'Amount is required.',
            'form.amount.min' => 'Amount must be greater than zero.',
        ]);

        try {
            DB::beginTransaction();

            if ($this->editingId) {
                $data = [
                    'rent_out_id' => $this->form['rent_out_id'],
                    'name' => $this->form['name'],
                    'amount' => $this->form['amount'],
                    'description' => $this->form['description'] ?? '',
                ];
                $response = (new UpdateAction)->execute($data, $this->editingId);
            } else {
                $rentOut = RentOut::findOrFail($this->form['rent_out_id']);
                $data = [
                    'rent_out_id' => $this->form['rent_out_id'],
                    'name' => $this->form['name'],
                    'amount' => $this->form['amount'],
                    'description' => $this->form['description'] ?? '',
                    'tenant_id' => $rentOut->tenant_id,
                    'branch_id' => $rentOut->branch_id,
                    'created_by' => $rentOut->created_by,
                ];
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

    public function close()
    {
        $this->showModal = false;
    }

    public function render()
    {
        return view('livewire.rent-out.tabs.service-modal');
    }
}
