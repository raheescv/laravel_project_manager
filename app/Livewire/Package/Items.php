<?php

namespace App\Livewire\Package;

use App\Actions\Package\Item\CreateAction;
use App\Actions\Package\Item\DeleteAction;
use App\Actions\Package\Item\UpdateAction;
use App\Models\Package;
use App\Models\PackageItem;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class Items extends Component
{
    use WithPagination;

    public $package_id;

    protected $paginationTheme = 'bootstrap';

    public $item = [];

    public $showModal = false;

    public $editingId = null;

    public $selectedItems = [];

    public $showGenerateModal = false;

    public $generateForm = [
        'from_date' => '',
        'number_of_terms' => '',
        'frequency' => '',
        'status' => 'pending',
    ];

    public $previewDates = [];

    public $calendarData = [];

    public function mount($package_id)
    {
        $this->package_id = $package_id;
    }

    public function loadItems()
    {
        // This method is kept for compatibility but items are now loaded in render()
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
            $this->resetPage();
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
            $this->resetPage();
            $this->selectedItems = [];
        } catch (\Throwable $e) {
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function toggleSelectAll()
    {
        $package = Package::with('items')->find($this->package_id);
        $allItems = $package ? $package->items->pluck('id')->toArray() : [];

        if (count($this->selectedItems) === count($allItems) && count($allItems) > 0) {
            $this->selectedItems = [];
        } else {
            $this->selectedItems = $allItems;
        }
    }

    public function toggleSelectItem($id)
    {
        if (in_array($id, $this->selectedItems)) {
            $this->selectedItems = array_values(array_diff($this->selectedItems, [$id]));
        } else {
            $this->selectedItems[] = $id;
        }
    }

    public function deleteSelected()
    {
        if (empty($this->selectedItems)) {
            $this->dispatch('error', ['message' => 'Please select at least one item to delete.']);

            return;
        }

        try {
            $successCount = 0;
            $errorCount = 0;

            foreach ($this->selectedItems as $id) {
                $response = (new DeleteAction())->execute($id);
                if ($response['success']) {
                    $successCount++;
                } else {
                    $errorCount++;
                }
            }

            if ($successCount > 0) {
                $this->dispatch('success', ['message' => "Successfully deleted {$successCount} item(s)."]);
                $this->resetPage();
                $this->selectedItems = [];
            }

            if ($errorCount > 0) {
                $this->dispatch('error', ['message' => "Failed to delete {$errorCount} item(s)."]);
            }
        } catch (\Throwable $e) {
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function openGenerateModal()
    {
        // Get package with category to auto-populate frequency and no_of_visits
        $package = Package::with('packageCategory')->find($this->package_id);

        $frequency = 'daily';
        $numberOfTerms = '';

        if ($package && $package->packageCategory) {
            $category = $package->packageCategory;
            // Use category frequency if available, otherwise default to 'daily'
            if ($category->frequency) {
                $frequency = $category->frequency;
            }
            // Use category no_of_visits if available
            if ($category->no_of_visits) {
                $numberOfTerms = $category->no_of_visits;
            }
        }

        $this->generateForm = [
            'from_date' => now()->format('Y-m-d'),
            'number_of_terms' => $numberOfTerms,
            'frequency' => $frequency,
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
        $this->calendarData = [];
    }

    public function previewGenerationDates()
    {
        $this->validate([
            'generateForm.from_date' => 'required|date',
            'generateForm.number_of_terms' => 'required|integer|min:1',
            'generateForm.frequency' => 'required|in:'.implode(',', array_keys(packageFrequency())),
        ]);

        $this->previewDates = $this->generateDates(
            $this->generateForm['from_date'],
            $this->generateForm['number_of_terms'],
            $this->generateForm['frequency']
        );

        $this->prepareCalendarData();
    }

    private function prepareCalendarData()
    {
        $this->calendarData = [];

        if (empty($this->previewDates)) {
            return;
        }

        // Get the year range from preview dates
        $firstDate = Carbon::parse($this->previewDates[0]);
        $lastDate = Carbon::parse(end($this->previewDates));
        $startYear = $firstDate->year;
        $endYear = $lastDate->year;

        // Create a map of all term dates for quick lookup
        $termDatesMap = [];
        foreach ($this->previewDates as $date) {
            $carbon = Carbon::parse($date);
            $termDatesMap[$carbon->format('Y-m-d')] = true;
        }

        // Generate calendar for each year
        for ($year = $startYear; $year <= $endYear; $year++) {
            $yearData = [
                'year' => $year,
                'months' => [],
            ];

            // Generate all 12 months for the year
            for ($month = 1; $month <= 12; $month++) {
                $firstDayOfMonth = Carbon::create($year, $month, 1);
                $daysInMonth = $firstDayOfMonth->daysInMonth;
                $startDayOfWeek = $firstDayOfMonth->dayOfWeek; // 0 = Sunday, 6 = Saturday
                $monthName = $firstDayOfMonth->format('M');

                // Build compact calendar grid (only 6 weeks max for compact view)
                $weeks = [];
                $currentWeek = [];

                // Add empty cells for days before the first day of the month
                for ($i = 0; $i < $startDayOfWeek; $i++) {
                    $currentWeek[] = null;
                }

                // Add days of the month
                for ($day = 1; $day <= $daysInMonth; $day++) {
                    $dateKey = Carbon::create($year, $month, $day)->format('Y-m-d');
                    $isTerm = isset($termDatesMap[$dateKey]);

                    $currentWeek[] = [
                        'day' => $day,
                        'isTerm' => $isTerm,
                        'date' => $dateKey,
                    ];

                    if (count($currentWeek) == 7) {
                        $weeks[] = $currentWeek;
                        $currentWeek = [];
                    }
                }

                // Fill remaining days in the last week
                while (count($currentWeek) < 7 && count($currentWeek) > 0) {
                    $currentWeek[] = null;
                }
                if (! empty($currentWeek)) {
                    $weeks[] = $currentWeek;
                }

                // Limit to 6 weeks for compact view
                $weeks = array_slice($weeks, 0, 6);

                $yearData['months'][] = [
                    'month' => $monthName,
                    'monthNum' => $month,
                    'weeks' => $weeks,
                ];
            }

            $this->calendarData[] = $yearData;
        }
    }

    public function generateAndSave()
    {
        $this->validate([
            'generateForm.from_date' => 'required|date',
            'generateForm.number_of_terms' => 'required|integer|min:1',
            'generateForm.frequency' => 'required|in:'.implode(',', array_keys(packageFrequency())),
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
                $this->resetPage();
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
                case 'monthly':
                    $currentDate->addMonth();
                    break;
                case 'yearly':
                    $currentDate->addYear();
                    break;
            }
        }

        return $dates;
    }

    public function render()
    {
        $package = Package::with('items')->find($this->package_id);
        $items = $package ? $package->items()->orderBy('date', 'asc')->paginate(10) : collect([]);

        return view('livewire.package.items', [
            'items' => $items,
        ]);
    }
}
