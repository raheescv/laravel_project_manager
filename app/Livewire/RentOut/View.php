<?php

namespace App\Livewire\RentOut;

use App\Actions\RentOut\VacateAction;
use App\Livewire\RentOut\Concerns\HasPaymentTermManagement;
use App\Support\RentOutConfig;
use Livewire\Component;

class View extends Component
{
    use HasPaymentTermManagement;

    public $agreementType = 'lease';

    public $showVacateModal = false;

    public $vacateDate = '';

    public function mount($id, $agreementType = 'lease')
    {
        $this->agreementType = $agreementType;
        $this->loadRentOut($id);
        $this->vacateDate = $this->rentOut->vacate_date?->format('Y-m-d') ?? '';
    }

    public function getConfigProperty(): RentOutConfig
    {
        return RentOutConfig::make($this->agreementType);
    }

    public function openVacateModal()
    {
        $this->vacateDate = $this->rentOut->vacate_date?->format('Y-m-d') ?? '';
        $this->showVacateModal = true;
    }

    public function saveVacate()
    {
        $result = (new VacateAction())->execute($this->rentOut->id, $this->vacateDate ?: null);

        if ($result['success']) {
            $this->showVacateModal = false;
            $this->loadRentOut();
            $this->dispatch('rent-out-updated');
            session()->flash('success', $result['message']);
        } else {
            $this->addError('vacateDate', $result['message']);
        }
    }

    public function render()
    {
        return view('livewire.rent-out.view', [
            'config' => $this->config,
        ]);
    }
}
