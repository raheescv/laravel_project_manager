<?php

namespace App\Livewire\RentOut\Tabs;

use App\Enums\RentOut\AgreementType;
use App\Helpers\Facades\RentOutTransactionHelper;
use App\Models\RentOut;
use App\Models\RentOutRoi;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class RoiModal extends Component
{
    public ?int $rentOutId = null;

    public $date = '';

    public $startDate = '';

    public $endDate = '';

    public $noOfMonths = 0;

    public $salePrice = 0;

    public $perMonthRoi = '';

    public $amount = 0;

    public $remark = '';

    public $description = '';

    #[On('open-roi-modal')]
    public function openModal($rentOutId)
    {
        $this->rentOutId = $rentOutId;
        $rentOut = RentOut::findOrFail($rentOutId);

        $this->date = now()->format('Y-m-d');
        $this->startDate = $rentOut->start_date->format('Y-m-d');
        $this->endDate = now()->endOfYear()->format('Y-m-d');
        $this->salePrice = (float) $rentOut->rent;
        $this->perMonthRoi = '';
        $this->remark = '';

        $this->calculateFields();
        $this->resetValidation();
        $this->dispatch('ToggleRoiModal');
    }

    public function updated($key, $value)
    {
        if (in_array($key, ['startDate', 'endDate', 'perMonthRoi'])) {
            $this->calculateFields();
        }
    }

    /**
     * Months are counted like the service charge: inclusive days over 30,
     * rounded. ROI is a flat monthly figure, so the amount is months × ROI.
     */
    protected function calculateFields(): void
    {
        $noOfDays = 0;
        if ($this->startDate && $this->endDate) {
            $noOfDays = max((int) Carbon::parse($this->startDate)->diffInDays(Carbon::parse($this->endDate), false) + 1, 0);
        }

        $this->noOfMonths = (int) round($noOfDays / 30);
        $perMonthRoi = is_numeric($this->perMonthRoi) ? (float) $this->perMonthRoi : 0;
        $this->amount = round($perMonthRoi * $this->noOfMonths, 2);

        $this->description = $this->startDate && $this->endDate
            ? 'ROI for the period '.systemDate($this->startDate).' to '.systemDate($this->endDate)
            : '';
    }

    /**
     * Yearly return on the sale price the monthly ROI amounts to, in percent.
     */
    public function getYieldProperty(): float
    {
        if (! is_numeric($this->perMonthRoi) || $this->salePrice <= 0) {
            return 0;
        }

        return round($this->perMonthRoi * 12 / $this->salePrice * 100, 2);
    }

    protected $rules = [
        'date' => 'required|date',
        'startDate' => 'required|date',
        'endDate' => 'required|date|after_or_equal:startDate',
        'perMonthRoi' => 'required|numeric|min:0.01',
        'amount' => 'required|numeric|min:0.01',
    ];

    protected $messages = [
        'date.required' => 'Date is required.',
        'startDate.required' => 'Start date is required.',
        'endDate.required' => 'End date is required.',
        'endDate.after_or_equal' => 'End date must be on or after the start date.',
        'perMonthRoi.required' => 'Per month ROI is required.',
        'perMonthRoi.min' => 'Per month ROI must be greater than zero.',
        'amount.min' => 'Amount must be greater than zero - the period is shorter than a month.',
    ];

    public function save()
    {
        abort_unless(auth()->user()?->can('rent out roi.create'), 403);
        $this->calculateFields();
        $this->validate();

        try {
            DB::beginTransaction();

            $rentOut = RentOut::findOrFail($this->rentOutId);
            if ($rentOut->agreement_type !== AgreementType::Lease) {
                throw new \Exception('ROI applies to sale agreements only.');
            }

            $roi = RentOutRoi::create([
                'tenant_id' => $rentOut->tenant_id,
                'branch_id' => $rentOut->branch_id,
                'rent_out_id' => $rentOut->id,
                'date' => $this->date,
                'start_date' => $this->startDate,
                'end_date' => $this->endDate,
                'no_of_months' => $this->noOfMonths,
                'sale_price' => $this->salePrice,
                'per_month_roi' => $this->perMonthRoi,
                'amount' => $this->amount,
                'description' => $this->description,
                'remark' => $this->remark,
                'created_by' => Auth::id(),
            ]);

            $response = RentOutTransactionHelper::storeRoi(
                $rentOut->id,
                $this->date,
                $this->amount,
                $roi->id,
                $this->remark ?: $this->description
            );

            if (! $response['success']) {
                throw new \Exception($response['message']);
            }

            DB::commit();
            $this->dispatch('ToggleRoiModal');
            $this->dispatch('rent-out-updated');
            $this->dispatch('success', message: 'ROI added successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            $this->dispatch('error', message: $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.rent-out.tabs.roi-modal');
    }
}
