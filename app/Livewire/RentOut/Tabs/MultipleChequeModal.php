<?php

namespace App\Livewire\RentOut\Tabs;

use App\Actions\RentOut\Cheque\CreateAction;
use App\Models\RentOut;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class MultipleChequeModal extends Component
{
    public bool $showModal = false;

    public ?int $rentOutId = null;

    // Form fields
    public string $multiStartNo = '';

    public string $multiBankName = '';

    public $multiAmount = 0;

    public string $multiStartDate = '';

    public int $multiCount = 12;

    public string $multiFrequency = 'Monthly';

    public string $multiPayeeName = '';

    // Generated preview list
    public array $previewList = [];

    #[On('open-multiple-cheque-modal')]
    public function openModal(
        $rentOutId = null,
        $startNo = '',
        $bankName = '',
        $amount = 0,
        $startDate = '',
        $count = 12,
        $frequency = 'Monthly',
        $payeeName = ''
    ) {
        $this->rentOutId = $rentOutId;
        $this->multiStartNo = $startNo;
        $this->multiBankName = $bankName;
        $this->multiAmount = $amount;
        $this->multiStartDate = $startDate;
        $this->multiCount = $count;
        $this->multiFrequency = $frequency;
        $this->multiPayeeName = $payeeName;
        $this->generatePreview();
        $this->showModal = true;
    }

    public function updatedMultiStartNo()
    {
        $this->generatePreview();
    }

    public function updatedMultiBankName()
    {
        $this->generatePreview();
    }

    public function updatedMultiPayeeName()
    {
        $this->generatePreview();
    }

    public function updatedMultiCount()
    {
        $this->generatePreview();
    }

    public function updatedMultiAmount()
    {
        $this->generatePreview();
    }

    public function updatedMultiStartDate()
    {
        $this->generatePreview();
    }

    public function updatedMultiFrequency()
    {
        $this->generatePreview();
    }

    public function generatePreview(): void
    {
        $this->previewList = [];

        if (! $this->multiStartNo || ! $this->multiCount || ! $this->multiAmount || ! $this->multiStartDate) {
            return;
        }

        $freqParams = $this->getFrequencyParams($this->multiFrequency);

        for ($i = 0; $i < $this->multiCount; $i++) {
            try {
                $date = Carbon::parse($this->multiStartDate);

                match ($freqParams['unit']) {
                    'months' => $date->addMonths($i * $freqParams['multiplier']),
                    'years' => $date->addYears($i * $freqParams['multiplier']),
                    default => $date->addMonths($i),
                };

                // Increment cheque number
                $chequeNo = $this->incrementChequeNo($this->multiStartNo, $i);

                $this->previewList[] = [
                    'cheque_no' => $chequeNo,
                    'date' => $date->format('Y-m-d'),
                    'amount' => $this->multiAmount,
                    'bank_name' => $this->multiBankName,
                    'payee_name' => $this->multiPayeeName,
                ];
            } catch (\Exception $e) {
                break;
            }
        }
    }

    protected function incrementChequeNo(string $startNo, int $increment): string
    {
        // Extract trailing numeric part and increment it
        if (preg_match('/^(.*?)(\d+)$/', $startNo, $matches)) {
            $prefix = $matches[1];
            $number = $matches[2];
            $length = strlen($number);
            $newNumber = str_pad((int) $number + $increment, $length, '0', STR_PAD_LEFT);

            return $prefix.$newNumber;
        }

        // If no numeric part, just append the increment
        return $startNo.($increment > 0 ? $increment : '');
    }

    protected function getFrequencyParams(string $frequency): array
    {
        return match ($frequency) {
            'Monthly' => ['unit' => 'months', 'multiplier' => 1],
            'Quarterly' => ['unit' => 'months', 'multiplier' => 3],
            'Half Yearly' => ['unit' => 'months', 'multiplier' => 6],
            'Yearly' => ['unit' => 'years', 'multiplier' => 1],
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

            $rentOut = RentOut::findOrFail($this->rentOutId);

            foreach ($this->previewList as $item) {
                $data = [
                    'rent_out_id' => $this->rentOutId,
                    'cheque_no' => $item['cheque_no'],
                    'bank_name' => $item['bank_name'] ?? $this->multiBankName,
                    'amount' => $item['amount'],
                    'date' => $item['date'],
                    'payee_name' => $item['payee_name'] ?? $this->multiPayeeName,
                    'status' => 'uncleared',
                    'remarks' => '',
                    'tenant_id' => $rentOut->tenant_id,
                    'branch_id' => $rentOut->branch_id,
                    'created_by' => $rentOut->created_by,
                ];
                $response = (new CreateAction)->execute($data);
                if (! $response['success']) {
                    throw new \Exception($response['message']);
                }
            }

            DB::commit();
            $this->showModal = false;
            $this->dispatch('rent-out-updated');
            $this->dispatch('success', message: 'Successfully created '.count($this->previewList).' cheques.');
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
        return collect($this->previewList)->sum(fn ($item) => (float) ($item['amount'] ?? 0));
    }

    public function render()
    {
        return view('livewire.rent-out.tabs.multiple-cheque-modal');
    }
}
