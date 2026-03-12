<?php

namespace App\Livewire\RentOut\Tabs;

use App\Actions\RentOut\PaymentTerm\CreateAction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class MultiplePaymentTermModal extends Component
{
    public bool $showModal = false;

    public ?int $rentOutId = null;

    public string $defaultLabel = 'rent payment';

    // Form fields
    public string $fromDate = '';

    public int $noOfTerms = 12;

    public $rent = 0;

    public string $frequency = 'Monthly';

    public ?string $endDate = null;

    // Agreement info summary
    public array $info = [];

    // Generated preview list
    public array $previewList = [];

    #[On('open-multiple-term-modal')]
    public function openModal(
        $fromDate = '',
        $noOfTerms = 12,
        $rent = 0,
        $frequency = 'Monthly',
        $endDate = null,
        $info = [],
        $rentOutId = null,
        $defaultLabel = 'rent payment'
    ) {
        $this->fromDate = $fromDate;
        $this->noOfTerms = $noOfTerms;
        $this->rent = $rent;
        $this->frequency = $frequency;
        $this->endDate = $endDate;
        $this->info = $info;
        $this->rentOutId = $rentOutId;
        $this->defaultLabel = $defaultLabel;
        $this->generatePreview();
        $this->showModal = true;
    }

    public function updatedFromDate()
    {
        $this->generatePreview();
    }

    public function updatedNoOfTerms()
    {
        $this->generatePreview();
    }

    public function updatedRent()
    {
        $this->generatePreview();
    }

    public function updatedFrequency()
    {
        $this->generatePreview();
    }

    public function generatePreview(): void
    {
        $this->previewList = [];

        if (! $this->fromDate || ! $this->noOfTerms || ! $this->rent) {
            return;
        }

        $freqParams = $this->getFrequencyParams($this->frequency);

        for ($i = 0; $i < $this->noOfTerms; $i++) {
            try {
                $date = Carbon::parse($this->fromDate);

                match ($freqParams['unit']) {
                    'days' => $date->addDays($i * $freqParams['multiplier']),
                    'weeks' => $date->addWeeks($i * $freqParams['multiplier']),
                    'months' => $date->addMonths($i * $freqParams['multiplier']),
                    'years' => $date->addYears($i * $freqParams['multiplier']),
                    default => $date->addMonths($i),
                };

                if ($this->endDate && $date->gt(Carbon::parse($this->endDate)->endOfDay())) {
                    break;
                }

                $this->previewList[] = [
                    'date' => $date->format('Y-m-d'),
                    'rent' => $this->rent,
                    'discount' => 0,
                    'remark' => '',
                ];
            } catch (\Exception $e) {
                break;
            }
        }
    }

    protected function getFrequencyParams(string $frequency): array
    {
        return match ($frequency) {
            'Daily' => ['unit' => 'days', 'multiplier' => 1],
            'Weekly' => ['unit' => 'weeks', 'multiplier' => 1],
            'Bi-Weekly' => ['unit' => 'weeks', 'multiplier' => 2],
            'Monthly' => ['unit' => 'months', 'multiplier' => 1],
            'Quarterly' => ['unit' => 'months', 'multiplier' => 3],
            'Half Yearly' => ['unit' => 'months', 'multiplier' => 6],
            'Yearly' => ['unit' => 'years', 'multiplier' => 1],
            'One Time' => ['unit' => 'years', 'multiplier' => 100],
            default => ['unit' => 'months', 'multiplier' => 1],
        };
    }

    public function save()
    {
        if (empty($this->previewList)) {
            return;
        }

        try {
            DB::beginTransaction();
            foreach ($this->previewList as $item) {
                $data = [
                    'rent_out_id' => $this->rentOutId,
                    'due_date' => $item['date'],
                    'label' => $this->defaultLabel,
                    'amount' => $item['rent'],
                    'discount' => $item['discount'] ?? 0,
                    'remarks' => $item['remark'] ?? '',
                ];
                $response = (new CreateAction)->execute($data);
                if (! $response['success']) {
                    throw new \Exception($response['message']);
                }
            }
            DB::commit();
            $this->showModal = false;
            $this->dispatch('rent-out-updated');
            $this->dispatch('success', message: 'Successfully created '.count($this->previewList).' payment terms.');
        } catch (\Exception $e) {
            DB::rollback();
            $this->dispatch('error', message: $e->getMessage());
        }
    }

    public function close()
    {
        $this->showModal = false;
    }

    public function getPreviewTotalProperty(): float
    {
        return collect($this->previewList)->sum(fn ($item) => (float) ($item['rent'] ?? 0));
    }

    public function getPreviewDiscountTotalProperty(): float
    {
        return collect($this->previewList)->sum(fn ($item) => (float) ($item['discount'] ?? 0));
    }

    public function render()
    {
        return view('livewire.rent-out.tabs.multiple-payment-term-modal');
    }
}
