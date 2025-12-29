<?php

namespace App\Livewire\Package;

use App\Actions\Package\Item\CreateAction;
use App\Actions\Package\Item\DeleteAction;
use App\Actions\Package\Item\UpdateAction;
use App\Models\Package;
use App\Models\PackageItem;
use Carbon\Carbon;
use Livewire\Component;

class Items extends Component
{
    public $package_id;

    public $items = [];

    public $item = [];

    public $showModal = false;

    public $editingId = null;

    public $showGenerateModal = false;

    public $generateForm = [
        'from_date' => '',
        'number_of_terms' => '',
        'frequency' => '',
        'status' => 'pending',
    ];

    public $previewDates = [];

    public function mount($package_id)
    {
        $this->package_id = $package_id;
        $this->loadItems();
    }

    public function loadItems()
    {
        $package = Package::with('items')->find($this->package_id);
        $this->items = $package ? $package->items->toArray() : [];
    }

    public function openModal($id = null)
    {
        $this->editingId = $id;
        if ($id) {
            $item = PackageItem::find($id);
            $this->item = $item->toArray();
        } else {
            $this->item = [
                'package_id' => $this->package_id,
                'date' => now()->format('Y-m-d'),
                'rescheduled_date' => null,
                'notes' => '',
                'status' => 'pending',
            ];
        }
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->item = [];
        $this->editingId = null;
    }

    public function save()
    {
        $this->validate([
            'item.date' => 'required|date',
            'item.rescheduled_date' => 'nullable|date',
            'item.status' => 'required|in:visited,rescheduled,pending',
            'item.notes' => 'nullable|string',
        ]);

        try {
            if ($this->editingId) {
                $response = (new UpdateAction())->execute($this->item, $this->editingId);
            } else {
                $response = (new CreateAction())->execute($this->item);
            }

            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }

            $this->dispatch('success', ['message' => $response['message']]);
            $this->loadItems();
            $this->closeModal();
        } catch (\Throwable $e) {
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function delete($id)
    {
        try {
            $response = (new DeleteAction())->execute($id);
            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }
            $this->dispatch('success', ['message' => $response['message']]);
            $this->loadItems();
        } catch (\Throwable $e) {
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function openGenerateModal()
    {
        $this->generateForm = [
            'from_date' => now()->format('Y-m-d'),
            'number_of_terms' => '',
            'frequency' => '',
            'status' => 'pending',
        ];
        $this->previewDates = [];
        $this->showGenerateModal = true;
    }

    public function closeGenerateModal()
    {
        $this->showGenerateModal = false;
        $this->generateForm = [
            'from_date' => '',
            'number_of_terms' => '',
            'frequency' => '',
            'status' => 'pending',
        ];
        $this->previewDates = [];
    }

    public function previewGenerationDates()
    {
        $this->validate([
            'generateForm.from_date' => 'required|date',
            'generateForm.number_of_terms' => 'required|integer|min:1',
            'generateForm.frequency' => 'required|in:daily,weekly,bi_weekly,thrice_monthly,monthly',
        ]);

        $this->previewDates = $this->generateDates(
            $this->generateForm['from_date'],
            $this->generateForm['number_of_terms'],
            $this->generateForm['frequency']
        );
    }

    public function generateAndSave()
    {
        $this->validate([
            'generateForm.from_date' => 'required|date',
            'generateForm.number_of_terms' => 'required|integer|min:1',
            'generateForm.frequency' => 'required|in:daily,weekly,bi_weekly,thrice_monthly,monthly',
            'generateForm.status' => 'required|in:visited,rescheduled,pending',
        ]);

        try {
            $dates = $this->generateDates(
                $this->generateForm['from_date'],
                $this->generateForm['number_of_terms'],
                $this->generateForm['frequency']
            );

            $successCount = 0;
            $errorCount = 0;

            foreach ($dates as $date) {
                $itemData = [
                    'package_id' => $this->package_id,
                    'date' => $date,
                    'rescheduled_date' => null,
                    'notes' => '',
                    'status' => $this->generateForm['status'],
                ];

                $response = (new CreateAction())->execute($itemData);
                if ($response['success']) {
                    $successCount++;
                } else {
                    $errorCount++;
                }
            }

            if ($successCount > 0) {
                $this->dispatch('success', ['message' => "Successfully generated {$successCount} term(s)."]);
                $this->loadItems();
                $this->closeGenerateModal();
            }

            if ($errorCount > 0) {
                $this->dispatch('error', ['message' => "Failed to generate {$errorCount} term(s)."]);
            }
        } catch (\Throwable $e) {
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    private function generateDates($fromDate, $numberOfTerms, $frequency)
    {
        $dates = [];
        $currentDate = Carbon::parse($fromDate);

        for ($i = 0; $i < $numberOfTerms; $i++) {
            $dates[] = $currentDate->format('Y-m-d');

            switch ($frequency) {
                case 'daily':
                    $currentDate->addDay();
                    break;
                case 'weekly':
                    $currentDate->addWeek();
                    break;
                case 'bi_weekly':
                    $currentDate->addWeeks(2);
                    break;
                case 'thrice_monthly':
                    // Thrice a month: approximately every 10 days
                    $currentDate->addDays(10);
                    break;
                case 'monthly':
                    $currentDate->addMonth();
                    break;
            }
        }

        return $dates;
    }

    public function render()
    {
        return view('livewire.package.items');
    }
}
