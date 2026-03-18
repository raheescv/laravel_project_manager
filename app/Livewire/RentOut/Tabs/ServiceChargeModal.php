<?php

namespace App\Livewire\RentOut\Tabs;

use App\Actions\RentOut\Payment\StoreTransactionAction;
use App\Models\RentOut;
use App\Models\RentOutService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class ServiceChargeModal extends Component
{
    public ?int $rentOutId = null;

    public $date = '';

    public $startDate = '';

    public $endDate = '';

    public $noOfDays = 0;

    public $noOfMonths = 0;

    public $unitSize = 0;

    public $perSqMeterPrice = 10;

    public $perDayPrice = 0;

    public $amount = 0;

    public $remark = '';

    public $description = '';

    #[On('open-service-charge-modal')]
    public function openModal($rentOutId)
    {
        $this->rentOutId = $rentOutId;
        $rentOut = RentOut::with('property')->findOrFail($rentOutId);

        $this->date = now()->format('Y-m-d');
        $this->startDate = $rentOut->start_date?->format('Y-m-d') ?? now()->format('Y-m-d');
        $this->endDate = now()->endOfYear()->format('Y-m-d');
        $this->unitSize = (float) ($rentOut->property?->size ?? 0);
        $this->perSqMeterPrice = 10;
        $this->remark = '';

        $this->calculateFields();
        $this->resetValidation();
        $this->dispatch('ToggleServiceChargeModal');
    }

    public function updated($key, $value)
    {
        if (in_array($key, ['startDate', 'endDate', 'perSqMeterPrice', 'unitSize'])) {

            if ($key == 'perSqMeterPrice') {
                if (! is_numeric($value)) {
                    $this->perSqMeterPrice = 0;
                }
            }
            $this->calculateFields();
        }
    }

    protected function calculateFields(): void
    {
        $start = Carbon::parse($this->startDate);
        $end = Carbon::parse($this->endDate);

        $this->noOfDays = max($start->diffInDays($end) + 1, 0);
        $this->noOfMonths = round($this->noOfDays / 30);
        $this->perDayPrice = round($this->unitSize * $this->perSqMeterPrice * 12 / 365, 2);
        $this->amount = round($this->noOfDays * $this->perDayPrice, 2);

        $startDate = systemDate($this->startDate);
        $endDate = systemDate($this->endDate);
        $this->description = 'Service charge for the period '.$startDate.' to '.$endDate;
    }

    protected $rules = [
        'date' => 'required|date',
        'startDate' => 'required|date',
        'endDate' => 'required|date',
        'amount' => 'required|numeric|min:0.01',
        'perSqMeterPrice' => 'required|numeric|min:0.01',
    ];

    protected $messages = [
        'date.required' => 'Date is required.',
        'startDate.required' => 'Start date is required.',
        'endDate.required' => 'End date is required.',
        'amount.min' => 'Amount must be greater than zero.',
        'perSqMeterPrice.min' => 'Per sq meter price must be greater than zero.',
    ];

    public function save()
    {
        $this->validate();

        try {
            DB::beginTransaction();

            $rentOut = RentOut::findOrFail($this->rentOutId);

            // Store as a RentOutService record
            $service = RentOutService::create([
                'tenant_id' => $rentOut->tenant_id,
                'branch_id' => $rentOut->branch_id,
                'rent_out_id' => $this->rentOutId,
                'name' => 'Service Charge',
                'amount' => $this->amount,
                'description' => $this->description,
                'created_by' => auth()->id(),
            ]);

            // Store as RentOutTransaction (debit entry — charge to customer, no payment yet)
            $response = (new StoreTransactionAction())->charge($this->rentOutId, [
                'date' => $this->date,
                'amount' => $this->amount,
                'source' => 'ServiceCharge',
                'model' => 'RentOutService',
                'model_id' => $service->id,
                'paid_date' => $this->date,
                'reason' => 'Service Charge',
                'group' => 'Service Charge',
                'category' => 'Service Charge',
                'payment_type' => 'Services',
                'remark' => $this->remark ?: $this->description,
                'created_by' => auth()->id(),
            ]);

            if (! $response['success']) {
                throw new \Exception($response['message']);
            }

            DB::commit();
            $this->dispatch('ToggleServiceChargeModal');
            $this->dispatch('rent-out-updated');
            $this->dispatch('success', message: 'Service charge added successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            $this->dispatch('error', message: $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.rent-out.tabs.service-charge-modal');
    }
}
